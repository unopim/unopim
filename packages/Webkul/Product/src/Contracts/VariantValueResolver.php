<?php

namespace Webkul\Product\Contracts;

use Webkul\Product\Models\Product;

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

    /**
     * Fully resolve a product's `values` by walking its ancestor chain.
     */
    public function resolve(Product $product): array;

    /**
     * Batch-resolve inherited `values` for many rows at once: one
     * deduplicated ancestor-chain query per hierarchy depth present in the
     * batch, instead of lazy-loading `->parent` per row. Each row needs an
     * `id`, `parent_id` (nullable) and `values` (array or JSON string).
     *
     * A row with no `parent_id` has nothing to inherit, so it is omitted
     * from the result entirely rather than mapped to a synthetic non-null
     * value -- callers must fall back to the row's own `values` for any id
     * missing from the returned array, which preserves that value's
     * original shape (including `null`) instead of coercing it to `[]`.
     *
     * @param  iterable<array{id:int,parent_id:?int,values:mixed}|object{id:int,parent_id:?int,values:mixed}>  $rows
     * @return array<int, array>
     */
    public function resolveBatch(iterable $rows): array;
}
