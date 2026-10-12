<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

it('should reject non-numeric values for price attributes during bulk save', function () {
    $this->loginAsAdmin();

    $priceAttribute = Attribute::where('type', 'price')->first() ?? Attribute::factory()->create([
        'type'              => 'price',
        'value_per_channel' => false,
        'value_per_locale'  => false,
    ]);

    $product = Product::factory()->create();

    $response = $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            (string) $product->id => [
                $priceAttribute->code => ['USD' => 'abc-not-a-number'],
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.'.$priceAttribute->code.'.0', fn ($msg) => is_string($msg) && $msg !== '');
});

it('should accept numeric values for price attributes during bulk save', function () {
    $this->loginAsAdmin();

    $priceAttribute = Attribute::where('type', 'price')->first() ?? Attribute::factory()->create([
        'type'              => 'price',
        'value_per_channel' => false,
        'value_per_locale'  => false,
    ]);

    $product = Product::factory()->create();

    $response = $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            (string) $product->id => [
                $priceAttribute->code => ['USD' => '19.99'],
            ],
        ],
    ]);

    $response->assertOk();
});
