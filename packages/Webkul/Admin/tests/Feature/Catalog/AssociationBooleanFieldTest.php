<?php

use Webkul\Product\Models\Product;

it('binds the boolean association switch label to the control it toggles', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    $switchStart = strpos($content, 'assocField.type === \'boolean\'');

    expect($switchStart)->not->toBeFalse();

    $switchMarkup = substr($content, $switchStart, 2000);

    expect($switchMarkup)->toContain(':for="assocFieldName(type.code, index, assocField)"');

    $labelStart = strpos($switchMarkup, '<label');

    expect($labelStart)->not->toBeFalse();

    expect(substr($switchMarkup, $labelStart, 1500))
        ->toContain(':for="assocFieldName(type.code, index, assocField)"');
});
