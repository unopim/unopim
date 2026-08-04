<?php

namespace Webkul\Product\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Contracts\VariantValueResolver as VariantValueResolverContract;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\AbstractType;

class VariantValueResolver implements VariantValueResolverContract
{
    const CATEGORIES_KEY = 'categories';

    /** Scoped buckets and their key depth. */
    const SCOPED_KEYS = [
        'locale_specific'         => 1,
        'channel_specific'        => 1,
        'channel_locale_specific' => 2,
    ];

    /** @var array<int, array> */
    protected array $memo = [];

    public function resolve(Product $product): array
    {
        if (isset($this->memo[$product->id])) {
            return $this->memo[$product->id];
        }

        $chain = [];
        $node = $product;
        $guard = 0;

        while ($node && $guard++ < 10) {
            $chain[] = $node->values ?? [];
            $node = $node->parent;
        }

        return $this->memo[$product->id] = $this->mergeChain(array_reverse($chain));
    }

    public function resolveBatch(iterable $rows): array
    {
        $chains = [];

        $nextParentId = [];

        $rowsWithParent = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $row['id'] : $row->id;
            $parentId = is_array($row) ? ($row['parent_id'] ?? null) : ($row->parent_id ?? null);
            $values = is_array($row) ? ($row['values'] ?? null) : ($row->values ?? null);

            $chains[$id] = [$this->decodeValues($values)];

            if (! empty($parentId)) {
                $nextParentId[$id] = (int) $parentId;
                $rowsWithParent[$id] = true;
            }
        }

        $ancestorCache = [];

        $guard = 0;

        while (! empty($nextParentId) && $guard++ < 10) {
            $idsToFetch = array_diff(array_unique(array_values($nextParentId)), array_keys($ancestorCache));

            if (! empty($idsToFetch)) {
                DB::table('products')
                    ->whereIn('id', $idsToFetch)
                    ->get(['id', 'parent_id', 'values'])
                    ->each(function ($ancestor) use (&$ancestorCache) {
                        $ancestorCache[$ancestor->id] = [
                            'values'    => $this->decodeValues($ancestor->values),
                            'parent_id' => $ancestor->parent_id,
                        ];
                    });
            }

            foreach ($nextParentId as $rowId => $ancestorId) {
                $ancestor = $ancestorCache[$ancestorId] ?? null;

                if (! $ancestor) {
                    unset($nextParentId[$rowId], $rowsWithParent[$rowId]);

                    continue;
                }

                $chains[$rowId][] = $ancestor['values'];

                if (empty($ancestor['parent_id'])) {
                    unset($nextParentId[$rowId]);
                } else {
                    $nextParentId[$rowId] = (int) $ancestor['parent_id'];
                }
            }
        }

        $merged = array_map(
            fn (array $chainLeafToRoot) => $this->mergeChain(array_reverse($chainLeafToRoot)),
            $chains
        );

        return array_intersect_key($merged, $rowsWithParent);
    }

    protected function decodeValues(mixed $values): array
    {
        if (empty($values)) {
            return [];
        }

        return is_string($values) ? (json_decode($values, true) ?? []) : (array) $values;
    }

    public function mergeChain(array $chainRootToLeaf): array
    {
        $commonKey = AbstractType::COMMON_VALUES_KEY;

        $mergedCommon = [];
        $mergedScoped = [];
        $categories = null;

        foreach ($chainRootToLeaf as $values) {
            $mergedCommon = array_merge($mergedCommon, $values[$commonKey] ?? []);

            foreach (self::SCOPED_KEYS as $scopeKey => $depth) {
                $mergedScoped[$scopeKey] = $this->mergeScope(
                    $mergedScoped[$scopeKey] ?? [],
                    $values[$scopeKey] ?? [],
                    $depth
                );
            }

            if (array_key_exists(self::CATEGORIES_KEY, $values)) {
                $categories = $values[self::CATEGORIES_KEY];
            }
        }

        $leaf = end($chainRootToLeaf) ?: [];

        $leaf[$commonKey] = $mergedCommon;

        foreach ($mergedScoped as $scopeKey => $scopeValues) {
            if ($scopeValues !== []) {
                $leaf[$scopeKey] = $scopeValues;
            }
        }

        if ($categories !== null) {
            $leaf[self::CATEGORIES_KEY] = $categories;
        }

        return $leaf;
    }

    /** Overlay an ancestor's scoped bucket onto the values gathered so far. */
    protected function mergeScope(array $carry, array $values, int $depth): array
    {
        foreach ($values as $key => $nested) {
            if (! is_array($nested)) {
                continue;
            }

            $carry[$key] = $depth > 1
                ? $this->mergeScope($carry[$key] ?? [], $nested, $depth - 1)
                : array_merge($carry[$key] ?? [], $nested);
        }

        return $carry;
    }
}
