<?php

use Webkul\Product\Models\Product;

it('wraps the product edit form in the unsaved-changes tracker that renders save and discard', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('<v-unsaved-changes');
    expect($content)->toContain('id="product-edit-form"');
    expect($content)->toContain(trans('admin::app.components.form.unsaved-changes.save'));
    expect($content)->toContain(trans('admin::app.components.form.unsaved-changes.discard'));
});
