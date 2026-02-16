<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;

it('can create field mappings via bulk save', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'        => 'mapping-test', 'name' => 'Mapping Test', 'channel_type' => 'shopify',
        'credentials' => [], 'status' => 'connected',
    ]);

    $response = $this->put(route('admin.channel_connector.mappings.store', $connector->code), [
        'mappings' => [
            ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'export'],
            ['unopim_attribute_code' => 'price', 'channel_field' => 'price', 'direction' => 'export'],
        ],
    ]);

    $response->assertRedirect();
    expect(ChannelFieldMapping::where('channel_connector_id', $connector->id)->count())->toBe(2);
});

it('validates duplicate mapping same attribute+field combo', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'        => 'dup-mapping', 'name' => 'Dup', 'channel_type' => 'shopify',
        'credentials' => [], 'status' => 'connected',
    ]);

    $response = $this->put(route('admin.channel_connector.mappings.store', $connector->code), [
        'mappings' => [
            ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'export'],
            ['unopim_attribute_code' => 'name', 'channel_field' => 'title', 'direction' => 'import'],
        ],
    ]);

    $response->assertSessionHasErrors();
});

it('persists locale_mapping JSON correctly', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'        => 'locale-map', 'name' => 'Locale', 'channel_type' => 'salla',
        'credentials' => [], 'status' => 'connected',
    ]);

    $this->put(route('admin.channel_connector.mappings.store', $connector->code), [
        'mappings' => [
            [
                'unopim_attribute_code' => 'name', 'channel_field' => 'name', 'direction' => 'export',
                'locale_mapping'        => ['en_US' => 'en', 'ar_AE' => 'ar'],
            ],
        ],
    ]);

    $mapping = ChannelFieldMapping::where('channel_connector_id', $connector->id)->first();
    expect($mapping->locale_mapping)->toBe(['en_US' => 'en', 'ar_AE' => 'ar']);
});
