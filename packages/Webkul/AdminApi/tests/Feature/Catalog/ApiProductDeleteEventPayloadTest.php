<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Publication\Models\Publication;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();

    $this->payloads = [];

    foreach (['catalog.product.delete.before', 'catalog.product.delete.after'] as $event) {
        Event::listen($event, function ($payload) use ($event): void {
            $this->payloads[$event] = $payload;
        });
    }
});

it('dispatches the product id, not the sku, when a simple product is deleted over REST', function () {
    $product = Product::factory()->simple()->create();

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.products.delete', ['code' => $product->sku]))
        ->assertOk();

    expect($this->payloads)->toBe([
        'catalog.product.delete.before' => $product->id,
        'catalog.product.delete.after'  => $product->id,
    ]);
});

it('dispatches the product id, not the sku, when a configurable product is deleted over REST', function () {
    $product = Product::factory()->configurable()->create();

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.configurable_products.delete', ['code' => $product->sku]))
        ->assertOk();

    expect($this->payloads)->toBe([
        'catalog.product.delete.before' => $product->id,
        'catalog.product.delete.after'  => $product->id,
    ]);
});

it('dispatches the product id, not the sku, when a variant group is deleted over REST', function () {
    $configurable = app(ProductRepository::class)->create([
        'sku'                 => 'dep-config-'.uniqid(),
        'type'                => ProductType::Configurable->value,
        'attribute_family_id' => AttributeFamily::factory()->create()->id,
    ]);

    $group = Product::factory()->create([
        'parent_id' => $configurable->id,
        'type'      => ProductType::VariantGroup->value,
        'sku'       => 'dep-group-'.uniqid(),
    ]);

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.configurable_products.delete', ['code' => $group->sku]))
        ->assertOk();

    expect($this->payloads)->toBe([
        'catalog.product.delete.before' => $group->id,
        'catalog.product.delete.after'  => $group->id,
    ]);
});

it('deletes a product over REST whose numeric sku exceeds the integer range', function () {
    $product = Product::factory()->simple()->create(['sku' => '9000000001']);

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.products.delete', ['code' => $product->sku]))
        ->assertOk();

    expect(Product::whereKey($product->id)->exists())->toBeFalse();
});

it('does not block a REST delete because another product whose id matches the numeric sku is published', function () {
    $published = Publication::factory()->create()->product;

    $product = Product::factory()->simple()->create(['sku' => (string) $published->id]);

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.products.delete', ['code' => $product->sku]))
        ->assertOk();

    expect(Product::whereKey($product->id)->exists())->toBeFalse()
        ->and(Product::whereKey($published->id)->exists())->toBeTrue();
});
