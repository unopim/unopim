<?php

use Webkul\Product\Models\Product;
use Webkul\Product\Services\VariantValueResolver;

it('merges common values root-to-leaf with child overriding by key presence', function () {
    $resolver = new VariantValueResolver;

    $root = ['common' => ['brand' => 'Nike', 'material' => 'Cotton']];
    $group = ['common' => ['image' => 'red.jpg']];
    $leaf = ['common' => ['size' => 'S', 'sku' => 'TEE-RED-S']];

    $resolved = $resolver->mergeChain([$root, $group, $leaf]);

    expect($resolved['common'])->toMatchArray([
        'brand'    => 'Nike',
        'material' => 'Cotton',
        'image'    => 'red.jpg',
        'size'     => 'S',
        'sku'      => 'TEE-RED-S',
    ]);
});

it('lets a descendant override an ancestor key (override by presence)', function () {
    $resolver = new VariantValueResolver;

    $root = ['common' => ['price' => '10.00', 'brand' => 'Nike']];
    $leaf = ['common' => ['price' => '19.00']];

    $resolved = $resolver->mergeChain([$root, $leaf]);

    expect($resolved['common']['price'])->toBe('19.00')
        ->and($resolved['common']['brand'])->toBe('Nike');
});

it('preserves the leaf non-common scopes untouched', function () {
    $resolver = new VariantValueResolver;

    $root = ['common' => ['brand' => 'Nike']];
    $leaf = [
        'common'           => ['size' => 'S'],
        'channel_specific' => ['default' => ['seo_title' => 'Red S']],
    ];

    $resolved = $resolver->mergeChain([$root, $leaf]);

    expect($resolved['channel_specific'])->toBe(['default' => ['seo_title' => 'Red S']]);
});

it('binds the resolver contract to the implementation in the container', function () {
    $resolved = app(Webkul\Product\Contracts\VariantValueResolver::class);

    expect($resolved)->toBeInstanceOf(VariantValueResolver::class);
});

it('resolves a variant\'s values from its configurable parent', function () {
    $parent = Product::factory()->configurable()->create([
        'values' => ['common' => ['brand' => 'Nike', 'material' => 'Cotton']],
    ]);

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => ['size' => 'S', 'sku' => $parent->sku.'-S']],
    ]);

    $resolved = app(Webkul\Product\Contracts\VariantValueResolver::class)->resolve($variant);

    expect($resolved['common'])->toMatchArray([
        'brand'    => 'Nike',
        'material' => 'Cotton',
        'size'     => 'S',
    ]);
});

it('returns own values unchanged for a product with no parent', function () {
    $simple = Product::factory()->create([
        'values' => ['common' => ['sku' => 'STANDALONE', 'name' => 'Solo']],
    ]);

    $resolved = app(Webkul\Product\Contracts\VariantValueResolver::class)->resolve($simple);

    expect($resolved['common'])->toMatchArray(['sku' => 'STANDALONE', 'name' => 'Solo']);
});
