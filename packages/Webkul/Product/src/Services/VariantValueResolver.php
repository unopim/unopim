<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\VariantValueResolver as VariantValueResolverContract;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\AbstractType;

class VariantValueResolver implements VariantValueResolverContract
{
    const CATEGORIES_KEY = 'categories';

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
        $categories = null;

        foreach ($chainRootToLeaf as $values) {
            $mergedCommon = array_merge($mergedCommon, $values[$commonKey] ?? []);

            if (array_key_exists(self::CATEGORIES_KEY, $values)) {
                $categories = $values[self::CATEGORIES_KEY];
            }
        }

        $leaf = end($chainRootToLeaf) ?: [];

        $leaf[$commonKey] = $mergedCommon;

        if ($categories !== null) {
            $leaf[self::CATEGORIES_KEY] = $categories;
        }

        return $leaf;
    }
}
