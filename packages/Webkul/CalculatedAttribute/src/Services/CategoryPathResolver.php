<?php

namespace Webkul\CalculatedAttribute\Services;

use Illuminate\Support\Facades\DB;
use Webkul\CalculatedAttribute\Contracts\CategoryPathProvider;

/**
 * Resolves category paths from a tree loaded once per process.
 *
 * The naive implementation would walk `parent_id` with a query per level per
 * product, which is the one thing that would make compute-on-read expensive on
 * a large export. Instead the whole tree is read once into two flat maps —
 * categories number in the hundreds even on big catalogues — and every
 * subsequent lookup is pointer-chasing in memory.
 */
class CategoryPathResolver implements CategoryPathProvider
{
    /**
     * id => ['code' => string, 'parent_id' => ?int, 'names' => array<string, string>]
     *
     * @var array<int, array{code: string, parent_id: int|null, names: array<string, string>}>|null
     */
    protected ?array $byId = null;

    /**
     * code => id
     *
     * @var array<string, int>
     */
    protected array $idByCode = [];

    /**
     * Ancestor chains already computed, keyed by category id.
     *
     * @var array<int, array<int, int>>
     */
    protected array $chainCache = [];

    /**
     * {@inheritdoc}
     */
    public function deepestPath(array $codes, ?string $locale = null): array
    {
        $this->load();

        $deepest = null;
        $deepestChain = [];

        foreach ($codes as $code) {
            $id = $this->idByCode[$code] ?? null;

            if ($id === null) {
                continue;
            }

            $chain = $this->ancestorChain($id);

            /*
             * "Deepest" is the longest root-to-node chain. Ties keep the first
             * one seen, so the result is stable for a given assignment order
             * rather than depending on map iteration.
             */
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
        $this->load();

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
     * Forget the loaded tree. Long-running workers call this when categories
     * change underneath them; a normal request never needs it.
     */
    public function flush(): void
    {
        $this->byId = null;
        $this->idByCode = [];
        $this->chainCache = [];
    }

    /**
     * Root-to-node ids, excluding the technical root.
     *
     * Every UnoPim category descends from a single root node, so including it
     * would prefix every path with the same meaningless segment.
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

        /*
         * The guard is against a parent_id cycle in the data, which the nested
         * set should prevent but which would otherwise hang an export.
         */
        while ($cursor !== null && $guard++ < 100) {
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

    protected function render(int $id, ?string $locale): string
    {
        $node = $this->byId[$id] ?? null;

        if ($node === null) {
            return '';
        }

        if ($locale === null) {
            return $node['code'];
        }

        /*
         * Falls back to the code when a category has no name in this locale,
         * matching the "[code]" convention used elsewhere in the admin minus the
         * brackets, which would be noise in an export column.
         */
        return $node['names'][$locale] ?? $node['code'];
    }

    protected function load(): void
    {
        if ($this->byId !== null) {
            return;
        }

        $this->byId = [];
        $this->idByCode = [];

        $rows = DB::table('categories')
            ->select(['id', 'code', 'parent_id', 'additional_data'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $id = (int) $row->id;

            $this->byId[$id] = [
                'code'      => (string) $row->code,
                'parent_id' => $row->parent_id === null ? null : (int) $row->parent_id,
                'names'     => $this->extractNames($row->additional_data),
            ];

            $this->idByCode[(string) $row->code] = $id;
        }
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
