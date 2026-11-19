<?php

namespace Webkul\CalculatedAttribute\Services;

use Illuminate\Database\Eloquent\Builder;
use Webkul\CalculatedAttribute\Contracts\CategoryPathProvider;
use Webkul\Category\Repositories\CategoryRepository;

/**
 * Resolves category paths for the categories a product is actually in.
 *
 * Only the requested categories and their ancestors are read, and the result is
 * kept for the rest of the request or job: a product sits in a handful of
 * categories, and the products in one export mostly share them, so after the
 * first few rows nearly every lookup is answered from memory without ever
 * holding the whole tree.
 *
 * The naive alternative — walking `parent_id` with a query per level per
 * product — is the one thing that would make compute-on-read expensive on a
 * large export, which is why ancestors come back in a single nested-set query
 * instead.
 */
class CategoryPathResolver implements CategoryPathProvider
{
    /**
     * id => ['code' => string, 'parent_id' => ?int, 'names' => array<string, string>]
     *
     * @var array<int, array{code: string, parent_id: int|null, names: array<string, string>}>
     */
    protected array $byId = [];

    /**
     * code => id
     *
     * @var array<string, int>
     */
    protected array $idByCode = [];

    /**
     * Codes already looked up, including ones that matched nothing, so an
     * unknown code costs one query rather than one per product.
     *
     * @var array<string, true>
     */
    protected array $resolvedCodes = [];

    /**
     * Ancestor chains already computed, keyed by category id.
     *
     * @var array<int, array<int, int>>
     */
    protected array $chainCache = [];

    /**
     * How far {@see ancestorChain()} will walk before giving up.
     */
    protected const MAX_DEPTH = 100;

    public function __construct(
        protected CategoryRepository $categoryRepository,
    ) {}

    /**
     * {@inheritdoc}
     *
     * "Deepest" is the longest root-to-node chain. Ties keep the first one
     * seen, so the result is stable for a given assignment order rather than
     * depending on map iteration.
     */
    public function deepestPath(array $codes, ?string $locale = null): array
    {
        $this->load($codes);

        $deepest = null;
        $deepestChain = [];

        foreach ($codes as $code) {
            $id = $this->idByCode[$code] ?? null;

            if ($id === null) {
                continue;
            }

            $chain = $this->ancestorChain($id);

            if ($deepest === null || count($chain) > count($deepestChain)) {
                $deepest = $id;
                $deepestChain = $chain;
            }
        }

        if ($deepest === null) {
            return [];
        }

        return array_values(array_map(
            fn (int $id): string => $this->render($id, $locale),
            $deepestChain
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function labels(array $codes, ?string $locale = null): array
    {
        $this->load($codes);

        $labels = [];

        foreach ($codes as $code) {
            $id = $this->idByCode[$code] ?? null;

            if ($id !== null) {
                $labels[] = $this->render($id, $locale);
            }
        }

        return $labels;
    }

    /**
     * Forget everything loaded. Long-running workers call this when categories
     * change underneath them; a normal request never needs it.
     */
    public function flush(): void
    {
        $this->byId = [];
        $this->idByCode = [];
        $this->resolvedCodes = [];
        $this->chainCache = [];
    }

    /**
     * Root-to-node ids, excluding the technical root.
     *
     * Every UnoPim category descends from a single root node, so including it
     * would prefix every path with the same meaningless segment.
     *
     * The step limit guards against a `parent_id` cycle in the data, which the
     * nested set should prevent but which would otherwise hang an export.
     *
     * @return array<int, int>
     */
    protected function ancestorChain(int $id): array
    {
        if (isset($this->chainCache[$id])) {
            return $this->chainCache[$id];
        }

        $chain = [];
        $cursor = $id;
        $guard = 0;

        while ($cursor !== null && $guard++ < self::MAX_DEPTH) {
            $node = $this->byId[$cursor] ?? null;

            if ($node === null) {
                break;
            }

            if ($node['parent_id'] !== null) {
                array_unshift($chain, $cursor);
            }

            $cursor = $node['parent_id'];
        }

        return $this->chainCache[$id] = $chain;
    }

    /**
     * The label for one category: its localised name, or its code when the
     * category has no name in this locale.
     *
     * Falling back to the bare code matches the "[code]" convention used
     * elsewhere in the admin, minus the brackets, which would be noise in an
     * export column.
     */
    protected function render(int $id, ?string $locale): string
    {
        $node = $this->byId[$id] ?? null;

        if ($node === null) {
            return '';
        }

        if ($locale === null) {
            return $node['code'];
        }

        return $node['names'][$locale] ?? $node['code'];
    }

    /**
     * Make sure the given codes, and every ancestor of them, are in memory.
     *
     * @param  array<int, string>  $codes
     */
    protected function load(array $codes): void
    {
        $missing = [];

        foreach ($codes as $code) {
            if (is_string($code) && $code !== '' && ! isset($this->resolvedCodes[$code])) {
                $missing[$code] = true;
            }
        }

        if ($missing === []) {
            return;
        }

        $missing = array_keys($missing);

        foreach ($missing as $code) {
            $this->resolvedCodes[$code] = true;
        }

        $nodes = $this->query()->whereIn('code', $missing)->get();

        if ($nodes->isEmpty()) {
            return;
        }

        foreach ($nodes as $node) {
            $this->remember($node);
        }

        $this->loadAncestors($nodes->all());
    }

    /**
     * Pull in every ancestor of the given nodes, in one query.
     *
     * The nested set makes an ancestor exactly a row that encloses the node's
     * `_lft`/`_rgt` bounds, so the whole chain comes back without walking it.
     *
     * @param  array<int, object>  $nodes
     */
    protected function loadAncestors(array $nodes): void
    {
        $bounds = [];

        foreach ($nodes as $node) {
            if ($node->parent_id !== null) {
                $bounds[] = [(int) $node->_lft, (int) $node->_rgt];
            }
        }

        if ($bounds === []) {
            return;
        }

        $ancestors = $this->query()
            ->where(function (Builder $query) use ($bounds): void {
                foreach ($bounds as [$left, $right]) {
                    $query->orWhere(fn (Builder $inner): Builder => $inner
                        ->where('_lft', '<', $left)
                        ->where('_rgt', '>', $right));
                }
            })
            ->get();

        foreach ($ancestors as $ancestor) {
            $this->remember($ancestor);
        }
    }

    protected function query(): Builder
    {
        return $this->categoryRepository
            ->getModel()
            ->newQuery()
            ->select(['id', 'code', 'parent_id', '_lft', '_rgt', 'additional_data']);
    }

    protected function remember(object $node): void
    {
        $id = (int) $node->id;

        if (isset($this->byId[$id])) {
            return;
        }

        $this->byId[$id] = [
            'code'      => (string) $node->code,
            'parent_id' => $node->parent_id === null ? null : (int) $node->parent_id,
            'names'     => $this->extractNames($node->additional_data),
        ];

        $this->idByCode[(string) $node->code] = $id;
        $this->resolvedCodes[(string) $node->code] = true;
    }

    /**
     * Pull `locale_specific.<locale>.name` out of the stored JSON blob.
     *
     * @return array<string, string>
     */
    protected function extractNames(mixed $additionalData): array
    {
        if (is_string($additionalData)) {
            $additionalData = json_decode($additionalData, true);
        }

        if (! is_array($additionalData)) {
            return [];
        }

        $localeSpecific = $additionalData['locale_specific'] ?? null;

        if (! is_array($localeSpecific)) {
            return [];
        }

        $names = [];

        foreach ($localeSpecific as $locale => $payload) {
            $name = is_array($payload) ? ($payload['name'] ?? null) : null;

            if (is_string($name) && $name !== '') {
                $names[(string) $locale] = $name;
            }
        }

        return $names;
    }
}
