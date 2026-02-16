<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;

it('can list mappings via API', function () {
    $token = $this->createAdminApiToken();

    $connector = ChannelConnector::create([
        'code'        => 'api-map', 'name' => 'API Map', 'channel_type' => 'shopify',
        'credentials' => [], 'status' => 'connected',
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'sku', 'channel_field' => 'sku', 'direction' => 'export',
    ]);

    $response = $this->withToken($token)
        ->getJson(route('admin.api.channel_connector.mappings.index', $connector->code));

    $response->assertOk()->assertJsonStructure(['data']);
});

it('can bulk save mappings via API', function () {
    $token = $this->createAdminApiToken();

    $connector = ChannelConnector::create([
        'code'        => 'api-bulk', 'name' => 'Bulk', 'channel_type' => 'shopify',
        'credentials' => [], 'status' => 'connected',
    ]);

    $response = $this->withToken($token)
        ->putJson(route('admin.api.channel_connector.mappings.store', $connector->code), [
            'mappings' => [
                ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'export', 'locale_mapping' => ['en_US' => 'en']],
            ],
        ]);

    $response->assertOk();
    expect(ChannelFieldMapping::where('channel_connector_id', $connector->id)->count())->toBe(1);
});
