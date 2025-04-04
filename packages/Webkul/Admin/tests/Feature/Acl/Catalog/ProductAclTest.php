<?php

use Webkul\Product\Models\Product;

it('should not display the product list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.catalog.products.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the product list if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products']);

    $this->get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.index.title'));
});

it('should not be able to create a product if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products']);

    $this->post(route('admin.catalog.products.store'), [])
        ->assertSeeText('Unauthorized');
});

it('should be able to create a product if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.create']);

    $data = Product::factory()->definition();

    $data['type'] = 'simple';

    $this->post(route('admin.catalog.products.store', $data))
        ->assertOk()
        ->assertSessionHas('success', trans('admin::app.catalog.products.create-success'));

    $this->assertDatabaseHas($this->getFullTableName(Product::class), $data);
});

it('should not be able to copy a product if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products']);
    $product = Product::factory()->create();

    $this->post(route('admin.catalog.products.copy', ['id' => $product->id]))
        ->assertSeeText('Unauthorized');
});

it('should be able to copy a product if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.copy']);
    $product = Product::factory()->simple()->create();

    $productId = $product->id;

    $this->post(route('admin.catalog.products.copy', $productId))
        ->assertOk()
        ->assertJsonFragment([
            'redirect_url' => route('admin.catalog.products.edit', ++$productId),
        ]);
});

it('should not be able to edit a product if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products']);
    $product = Product::factory()->create();

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id]))
        ->assertSeeText('Unauthorized');
});

it('should be able to edit a product if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.edit']);

    $product = Product::factory()->simple()->create();

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.edit.title'));
});

it('should not be able to delete a product if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products']);
    $product = Product::factory()->create();

    $this->delete(route('admin.catalog.products.delete', ['id' => $product->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Product::class), ['id' => $product->id]);
});

it('should be able to delete a product if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.delete']);

    $product = Product::factory()->create();

    $this->delete(route('admin.catalog.products.delete', ['id' => $product->id]))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $product->id]);
});
