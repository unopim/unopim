<?php

use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAssociation;

it('stores a link with additional_data and resolves its relations', function () {
    $type = AssociationType::where('code', 'up_sells')->firstOrFail();

    $source = Product::factory()->create();
    $target = Product::factory()->create();

    $link = ProductAssociation::create([
        'product_id'           => $source->id,
        'association_type_id'  => $type->id,
        'related_product_id'   => $target->id,
        'position'             => 1,
        'additional_data'      => ['common' => ['quantity' => '2']],
    ]);

    expect($link->fresh()->additional_data)->toBe(['common' => ['quantity' => '2']])
        ->and($link->relatedProduct->id)->toBe($target->id)
        ->and($link->associationType->code)->toBe('up_sells');
});
