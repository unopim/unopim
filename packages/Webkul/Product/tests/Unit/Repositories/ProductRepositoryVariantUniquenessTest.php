<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

it('scopes variant uniqueness by type when a type is given', function () {
    $parent = Product::factory()->configurable()->create();

    $group = Product::factory()->create([
        'parent_id' => $parent->id,
        'type'      => 'variant_group',
        'sku'       => 'group-red',
    ]);
    $group->values = ['common' => ['color' => 'red', 'sku' => 'group-red']];
    $group->save();

    $simple = Product::factory()->create([
        'parent_id' => $parent->id,
        'type'      => 'simple',
        'sku'       => 'simple-red',
    ]);
    $simple->values = ['common' => ['color' => 'red', 'sku' => 'simple-red']];
    $simple->save();

    $repository = app(ProductRepository::class);

    expect($repository->isUniqueVariantForProduct($parent->id, ['color' => 'red'], null, '', 'variant_group'))
        ->toBeFalse()
        ->and($repository->isUniqueVariantForProduct($parent->id, ['color' => 'red'], null, '', 'configurable'))
        ->toBeTrue();
});
