<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\VariantValueResolver as VariantValueResolverContract;
use Webkul\Product\Type\AbstractType;

class VariantValueResolver implements VariantValueResolverContract
{
    public function mergeChain(array $chainRootToLeaf): array
    {
        $commonKey = AbstractType::COMMON_VALUES_KEY;

        $mergedCommon = [];

        foreach ($chainRootToLeaf as $values) {
            $mergedCommon = array_merge($mergedCommon, $values[$commonKey] ?? []);
        }

        $leaf = end($chainRootToLeaf) ?: [];

        $leaf[$commonKey] = $mergedCommon;

        return $leaf;
    }
}
