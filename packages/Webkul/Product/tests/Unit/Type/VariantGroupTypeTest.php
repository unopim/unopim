<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\VariantGroup;

uses(DatabaseTransactions::class);

it('registers an internal variant_group type not offered for manual creation', function () {
    $types = config('product_types');

    expect($types)->toHaveKey('variant_group')
        ->and($types['variant_group']['class'])->toBe(VariantGroup::class)
        ->and($types['variant_group']['internal'] ?? false)->toBeTrue();
});

it('resolves a variant_group product to the VariantGroup type instance', function () {
    $group = Product::factory()->create(['type' => 'variant_group']);

    expect($group->getTypeInstance())->toBeInstanceOf(VariantGroup::class);
});
