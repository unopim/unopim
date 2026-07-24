<?php

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

/*
 * Guards against the save-path N+1 (S1): AbstractType::processValues resolved the
 * attribute for every common field with a separate lookup, so a product with N
 * common attributes issued N lookups on every save. The attribute set must be
 * resolved once, independent of field count.
 */
function prepareValuesQueryCount(Product $product, int $commonFieldCount): int
{
    $common = [];

    for ($i = 1; $i <= $commonFieldCount; $i++) {
        $common["perf_field_{$i}"] = "value {$i}";
    }

    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $product->getTypeInstance()->prepareProductValues(['values' => ['common' => $common]], $product);

    return $queries;
}

it('resolves attributes once regardless of common field count (S1)', function () {
    $product = Product::factory()->simple()->create();

    $delta = prepareValuesQueryCount($product, 30) - prepareValuesQueryCount($product, 3);

    expect($delta)->toBeLessThanOrEqual(3);
});
