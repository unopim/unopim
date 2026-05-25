<?php

use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should respect the limit query parameter on the configurable products endpoint (Issue #743)', function () {
    // Ensure at least 3 configurable products exist.
    Product::factory()->configurable()->count(3)->create();

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.configurable_products.index', ['limit' => 1, 'page' => 1]));

    $response->assertOk();

    $data = $response->json('data');

    expect(is_array($data))->toBeTrue();
    expect(count($data))->toBe(1);
});
