<?php

namespace Webkul\Product\Services;

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
