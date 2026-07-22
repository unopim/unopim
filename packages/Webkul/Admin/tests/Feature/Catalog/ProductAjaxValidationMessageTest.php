<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\Product\Models\Product;

it('returns a field level message when a required product value is submitted empty via ajax', function () {
    $this->loginAsAdmin();

    $this->withoutMiddleware(PreventRequestForgery::class);

    $product = Product::factory()->simple()->withInitialValues()->create();

    $values = $product->values;

    $values['common']['sku'] = null;

    $response = $this->putJson(
        route('admin.catalog.products.update', $product->id),
        ['sku'         => $product->sku, 'values' => $values],
        ['X-Ajax-Form' => 'true']
    );

    $response->assertUnprocessable();

    expect($response->json('message'))->not->toBeEmpty()
        ->and($response->json('errors'))->toHaveKey('values[common][sku]');
});
