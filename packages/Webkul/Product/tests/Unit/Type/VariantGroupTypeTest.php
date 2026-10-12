<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\VariantGroup;

uses(DatabaseTransactions::class);

it('registers an internal variant_group type not offered for manual creation', function () {
    $types = config('product_types');

    expect($types)->toHaveKey(ProductType::VariantGroup->value)
        ->and($types[ProductType::VariantGroup->value]['class'])->toBe(VariantGroup::class)
        ->and($types[ProductType::VariantGroup->value]['internal'] ?? false)->toBeTrue();
});

it('resolves a variant_group product to the VariantGroup type instance', function () {
    $group = Product::factory()->create(['type' => ProductType::VariantGroup->value]);

    expect($group->getTypeInstance())->toBeInstanceOf(VariantGroup::class);
});
