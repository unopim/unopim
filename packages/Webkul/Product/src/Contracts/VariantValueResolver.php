<?php

namespace Webkul\Product\Contracts;

interface VariantValueResolver
{
    /**
     * Merge an ordered chain of `values` arrays (root ancestor -> leaf).
     *
     * The `common` scope is flattened with descendants overriding ancestors
     * by key presence; other scopes are taken from the leaf unchanged.
     *
     * @param  array<int, array>  $chainRootToLeaf
     */
    public function mergeChain(array $chainRootToLeaf): array;
}
