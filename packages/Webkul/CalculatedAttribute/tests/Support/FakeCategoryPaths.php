<?php

namespace Webkul\CalculatedAttribute\Tests\Support;

use Webkul\CalculatedAttribute\Contracts\CategoryPathProvider;

/**
 * A category tree held in an array, so the formula-language tests can exercise
 * category_path()/category_label_path() without a database.
 *
 * The real tree walk is covered separately against actual category rows in the
 * feature suite; this double exists to keep the language tests fast and to pin
 * the contract the production resolver must satisfy.
 */
class FakeCategoryPaths implements CategoryPathProvider
{
    /**
     * @param  array<string, array{parent?: string|null, names?: array<string, string>}>  $tree
     */
    public function __construct(
        protected array $tree = [],
    ) {}

    /**
     * {@inheritdoc}
     */
    public function deepestPath(array $codes, ?string $locale = null): array
    {
        $deepest = [];

        foreach ($codes as $code) {
            $chain = $this->chain($code);

            if (count($chain) > count($deepest)) {
                $deepest = $chain;
            }
        }

        return array_map(fn (string $code): string => $this->render($code, $locale), $deepest);
    }

    /**
     * {@inheritdoc}
     */
    public function labels(array $codes, ?string $locale = null): array
    {
        $labels = [];

        foreach ($codes as $code) {
            if (isset($this->tree[$code])) {
                $labels[] = $this->render($code, $locale);
            }
        }

        return $labels;
    }

    /**
     * @return array<int, string>
     */
    protected function chain(string $code): array
    {
        $chain = [];
        $cursor = $code;

        while ($cursor !== null && isset($this->tree[$cursor])) {
            array_unshift($chain, $cursor);

            $cursor = $this->tree[$cursor]['parent'] ?? null;
        }

        return $chain;
    }

    protected function render(string $code, ?string $locale): string
    {
        if ($locale === null) {
            return $code;
        }

        return $this->tree[$code]['names'][$locale] ?? $code;
    }
}
