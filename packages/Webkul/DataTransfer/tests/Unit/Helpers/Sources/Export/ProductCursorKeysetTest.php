<?php

use Webkul\DataTransfer\Helpers\Sources\Export\ProductCursor;
use Webkul\Product\Models\Product;

it('iterates every product id exactly once in ascending order using keyset pagination', function () {
    Product::factory()->count(25)->create();

    $expected = Product::orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

    $cursor = new ProductCursor(['filters' => []], new Product, 10);

    $seen = [];

    $cursor->rewind();

    while ($cursor->valid()) {
        $seen[] = (int) $cursor->current()['id'];
        $cursor->next();
    }

    expect($seen)->toBe($expected)
        ->and(count($seen))->toBe(count(array_unique($seen)));
});
