<?php

use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should delete a configurable product via DELETE (Issue #744)', function () {
    $product = Product::factory()->configurable()->create();

    $response = $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.configurable_products.delete', ['code' => $product->sku]));

    $response->assertOk()->assertJsonFragment(['success' => true]);

    $this->assertDatabaseMissing($product->getTable(), ['id' => $product->id, 'sku' => $product->sku]);
});
