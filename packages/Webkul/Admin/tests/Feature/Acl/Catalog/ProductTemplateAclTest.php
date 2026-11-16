<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

it('denies the template endpoints without the product edit permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products']);

    $product = Product::factory()->simple()->create();

    $this->getJson(route('admin.catalog.products.template_suggestions', ['id' => $product->id]))
        ->assertForbidden();

    $this->postJson(route('admin.catalog.products.template_preview', ['id' => $product->id]), ['attribute' => 'sku'])
        ->assertForbidden();
});

it('allows the template endpoints with the product edit permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.edit']);

    $product = Product::factory()->simple()->create();

    $attribute = Attribute::factory()->create(['type' => 'textarea', 'validation' => null, 'enable_templates' => 1]);

    $this->getJson(route('admin.catalog.products.template_suggestions', ['id' => $product->id]))
        ->assertOk();

    $this->postJson(route('admin.catalog.products.template_preview', ['id' => $product->id]), ['attribute' => $attribute->code])
        ->assertOk();
});
