<?php

use Webkul\Product\Models\Product;

it('labels every association link field control so validation messages never expose the request path', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    preg_match_all('/<[^>]*:rules="assocField\.rules"[^>]*>/', $content, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $tag) {
        expect($tag)->toContain(':label="assocField.label"');
    }
});
