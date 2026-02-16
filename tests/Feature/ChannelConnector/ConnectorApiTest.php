<?php

use Webkul\ChannelConnector\Models\ChannelConnector;

it('can list connectors via API', function () {
    $token = $this->createAdminApiToken();

    ChannelConnector::create([
        'code'         => 'api-list-test',
        'name'         => 'API Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'api.myshopify.com'],
        'status'       => 'connected',
    ]);

    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.connectors.index'));

    $response->assertOk()
        ->assertJsonStructure(['data']);
});

it('never exposes credentials in API response', function () {
    $token = $this->createAdminApiToken();

    ChannelConnector::create([
        'code'         => 'cred-test',
        'name'         => 'Cred Store',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'secret_token'],
        'status'       => 'connected',
    ]);

    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.connectors.show', 'cred-test'));

    $response->assertOk();

    $data = $response->json();
    expect($data)->not->toHaveKey('credentials');
    expect(json_encode($data))->not->toContain('secret_token');
});

it('can create connector via API', function () {
    $token = $this->createAdminApiToken();

    $response = $this->withToken($token)
        ->postJson(route('admin.api.channel_connector.connectors.store'), [
            'code'         => 'api-create',
            'name'         => 'API Created Store',
            'channel_type' => 'salla',
            'credentials'  => ['api_key' => 'test_key'],
        ]);

    $response->assertCreated();

    $this->assertDatabaseHas('channel_connectors', [
        'code' => 'api-create',
    ]);
});

it('can delete connector via API', function () {
    $token = $this->createAdminApiToken();

    ChannelConnector::create([
        'code'         => 'api-delete',
        'name'         => 'Delete via API',
        'channel_type' => 'easy_orders',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'disconnected',
    ]);

    $response = $this->withToken($token)
        ->deleteJson(route('admin.api.channel_connector.connectors.destroy', 'api-delete'));

    $response->assertOk();

    $this->assertDatabaseMissing('channel_connectors', ['code' => 'api-delete']);
});
