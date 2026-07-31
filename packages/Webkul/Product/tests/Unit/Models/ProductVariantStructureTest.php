<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;

uses(DatabaseTransactions::class);

it('associates a product with a variant structure', function () {
    $product = Product::factory()->configurable()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $product->attribute_family_id,
        'code'                => 'tshirt-color-size',
        'name'                => 'Color / Size',
        'levels'              => 2,
    ]);

    $product->variant_structure_id = $structure->id;
    $product->save();

    expect($product->refresh()->variantStructure->code)->toBe('tshirt-color-size');
});
