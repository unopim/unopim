<?php

use Webkul\ChannelConnector\Models\ChannelConnector;

it('can create a channel connector', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.channel_connector.connectors.store'), [
        'code'         => 'test-shopify',
        'name'         => 'Test Shopify Store',
        'channel_type' => 'shopify',
        'credentials'  => [
            'shop_url'     => 'test.myshopify.com',
            'access_token' => 'shpat_test_token',
        ],
        'status' => 'disconnected',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('channel_connectors', [
        'code'         => 'test-shopify',
        'name'         => 'Test Shopify Store',
        'channel_type' => 'shopify',
    ]);
});

it('validates required fields when creating connector', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.channel_connector.connectors.store'), []);

    $response->assertSessionHasErrors(['code', 'name', 'channel_type']);
});

it('validates unique code per tenant', function () {
    $this->loginAsAdmin();

    ChannelConnector::create([
        'code'         => 'duplicate-code',
        'name'         => 'First Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'first.myshopify.com'],
        'status'       => 'connected',
    ]);

    $response = $this->post(route('admin.channel_connector.connectors.store'), [
        'code'         => 'duplicate-code',
        'name'         => 'Second Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'second.myshopify.com'],
    ]);

    $response->assertSessionHasErrors(['code']);
});

it('validates channel_type must be valid', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.channel_connector.connectors.store'), [
        'code'         => 'test-invalid',
        'name'         => 'Invalid Channel',
        'channel_type' => 'invalid_type',
        'credentials'  => ['key' => 'value'],
    ]);

    $response->assertSessionHasErrors(['channel_type']);
});

it('can update a channel connector', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'update-test',
        'name'         => 'Original Name',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'test.myshopify.com'],
        'status'       => 'connected',
    ]);

    $response = $this->put(route('admin.channel_connector.connectors.update', $connector->code), [
        'name' => 'Updated Name',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('channel_connectors', [
        'code' => 'update-test',
        'name' => 'Updated Name',
    ]);
});

it('can delete a channel connector', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'delete-test',
        'name'         => 'Delete Me',
        'channel_type' => 'salla',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'disconnected',
    ]);

    $response = $this->delete(route('admin.channel_connector.connectors.destroy', $connector->code));

    $response->assertRedirect();

    $this->assertDatabaseMissing('channel_connectors', ['code' => 'delete-test']);
});

it('can list connectors with pagination', function () {
    $this->loginAsAdmin();

    ChannelConnector::create([
        'code'         => 'list-test-1',
        'name'         => 'Store 1',
        'channel_type' => 'shopify',
        'credentials'  => [],
        'status'       => 'connected',
    ]);

    $response = $this->get(route('admin.channel_connector.connectors.index'));

    $response->assertOk();
});
