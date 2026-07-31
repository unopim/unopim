<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;

uses(DatabaseTransactions::class);

it('exposes resolvedValues on the product model, inheriting from the parent', function () {
    $parent = Product::factory()->configurable()->create([
        'values' => ['common' => ['brand' => 'Nike', 'material' => 'Cotton']],
    ]);

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => ['size' => 'S', 'sku' => $parent->sku.'-S']],
    ]);

    expect($variant->resolvedValues()['common'])->toMatchArray([
        'brand'    => 'Nike',
        'material' => 'Cotton',
        'size'     => 'S',
    ]);
});

it('returns own values for a product without a parent', function () {
    $simple = Product::factory()->create([
        'values' => ['common' => ['sku' => 'SOLO', 'name' => 'Solo']],
    ]);

    expect($simple->resolvedValues()['common'])->toMatchArray(['sku' => 'SOLO', 'name' => 'Solo']);
});
