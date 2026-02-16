<?php

use Illuminate\Support\Facades\Http;
use Webkul\Shopify\Models\ShopifyCredentialsConfig;

it('should not display the shopify credentials index if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('shopify.credentials.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the shopify credentials index if has permission', function () {
    $this->loginWithPermissions(permissions: ['shopify', 'shopify.credentials']);

    $this->get(route('shopify.credentials.index'))
        ->assertSeeText(trans('shopify::app.components.layouts.sidebar.shopify'))
        ->assertStatus(200);
});

it('should not display the create shopify credentials form if does not have permission', function () {
    $this->loginWithPermissions();

    $this->post(route('shopify.credentials.store'))
        ->assertSeeText('Unauthorized');
});

it('should display the create shopify credentials form if has permission', function () {
    $this->loginWithPermissions(permissions: ['shopify.credentials.create']);

    Http::fake([
        'https://test.myshopify.com/admin/api/2023-04/graphql.json' => Http::response(['code' => 200], 200),
    ]);

    $shopifyCredential = [
        'accessToken' => 'test_access_token',
        'apiVersion'  => '2023-04',
        'shopUrl'     => 'https://test.myshopify.com',
    ];

    $this->post(route('shopify.credentials.store'), $shopifyCredential)
        ->assertStatus(200);
});

it('should not display the shopify credentials edit form if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('shopify.credentials.edit', ['id' => 1]))
        ->assertSeeText('Unauthorized');
});

it('should display the shopify credentials edit form if has permission', function () {
    $this->loginWithPermissions(permissions: ['shopify.credentials.edit']);

    $shopifyCredential = ShopifyCredentialsConfig::factory()->create();

    $this->get(route('shopify.credentials.edit', ['id' => $shopifyCredential->id]))
        ->assertStatus(200);
});

it('should not allow deleting shopify credentials if does not have permission', function () {
    $this->loginWithPermissions();

    $shopifyCredential = ShopifyCredentialsConfig::factory()->create();

    $this->delete(route('shopify.credentials.delete', ['id' => $shopifyCredential->id]))
        ->assertSeeText('Unauthorized');
});

it('should allow deleting shopify credentials if has permission', function () {
    $this->loginWithPermissions(permissions: ['shopify.credentials.delete']);
    $shopifyCredential = ShopifyCredentialsConfig::factory()->create();

    $this->delete(route('shopify.credentials.delete', $shopifyCredential->id))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(ShopifyCredentialsConfig::class), [
        'id' => $shopifyCredential->id,
    ]);
});

it('should not display the shopify export mappings if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.shopify.export-mappings', ['id' => 2]))
        ->assertSeeText('Unauthorized');
});

it('should display the shopify export mappings if has permission', function () {
    $this->loginWithPermissions(permissions: ['shopify', 'shopify.export-mappings']);

    $this->get(route('admin.shopify.export-mappings', 1))
        ->assertStatus(200)
        ->assertSeeText(trans('shopify::app.shopify.export.mapping.title'));
});

it('should not display the shopify settings if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.shopify.settings', ['id' => 1]))
        ->assertSeeText('Unauthorized');
});

it('should display the shopify settings if has permission', function () {
    $this->loginWithPermissions(permissions: ['shopify', 'shopify.settings']);

    $this->get(route('admin.shopify.settings', ['id' => 1]))
        ->assertSeeText(trans('shopify::app.components.layouts.sidebar.settings'))
        ->assertStatus(200);
});
