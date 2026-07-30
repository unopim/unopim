<?php

use Webkul\Product\Models\Product;

it('labels every association link field control so validation messages never expose the request path', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    $ruleBindings = substr_count($content, ':rules="assocField.rules"');
    $labelBindings = substr_count($content, ':label="assocField.label"');

    expect($ruleBindings)->toBeGreaterThan(0);
    expect($labelBindings)->toBe($ruleBindings);
});
