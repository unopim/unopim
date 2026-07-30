<?php

use Webkul\Product\Models\Product;

it('labels the association link remove action as remove product', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    $label = trans('admin::app.catalog.products.edit.links.remove-product');

    expect($label)->not->toBe('admin::app.catalog.products.edit.links.remove-product');

    expect($content)->toContain('title="'.$label.'"');
    expect($content)->toContain('aria-label="'.$label.'"');
});

it('stacks each association link field label above its control', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('flex: 1 1 220px; min-width: 200px; max-width: 320px');
    expect($content)->not->toContain('width: 145px');
});
