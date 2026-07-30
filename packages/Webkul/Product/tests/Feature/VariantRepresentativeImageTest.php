<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

function productWithImage(array $overrides = [], ?string $image = null): Product
{
    $product = Product::factory()->create($overrides);

    $product->values = ['common' => $image === null ? [] : ['image' => $image]];
    $product->save();

    return $product->refresh();
}

it('shows a child variant image on the parent when the parent has none', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => 'configurable']);
    $child = productWithImage(['type' => 'simple', 'parent_id' => $parent->id], 'product/1/child.jpg');

    expect($child->getProductDisplayImage())->toBe('product/1/child.jpg');
    expect($parent->refresh()->getProductDisplayImage())->toBe('product/1/child.jpg');
});

it('walks down to a grandchild variant for the top level parent', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => 'configurable']);
    $subParent = productWithImage(['type' => 'configurable', 'parent_id' => $parent->id]);
    $child = productWithImage(['type' => 'simple', 'parent_id' => $subParent->id], 'product/2/grandchild.jpg');

    expect($subParent->refresh()->getProductDisplayImage())->toBe('product/2/grandchild.jpg');
    expect($parent->refresh()->getProductDisplayImage())->toBe('product/2/grandchild.jpg');
});

it('keeps the products own image ahead of any variant image', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => 'configurable'], 'product/3/parent.jpg');
    productWithImage(['type' => 'simple', 'parent_id' => $parent->id], 'product/3/child.jpg');

    expect($parent->refresh()->getProductDisplayImage())->toBe('product/3/parent.jpg');
});

it('never writes the borrowed image back onto the parent values', function () {
    Attribute::firstOrCreate(['code' => 'image'], ['type' => 'image']);

    $parent = productWithImage(['type' => 'configurable']);
    productWithImage(['type' => 'simple', 'parent_id' => $parent->id], 'product/4/child.jpg');

    $parent->refresh()->getProductDisplayImage();

    expect($parent->refresh()->values['common']['image'] ?? null)->toBeNull();
});
