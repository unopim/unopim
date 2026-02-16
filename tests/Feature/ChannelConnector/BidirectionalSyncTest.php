<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\ChannelConnector\Jobs\ProcessWebhookJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('routes common attribute to values common bucket', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'test_weight',
        'type'              => 'text',
        'value_per_locale'  => false,
        'value_per_channel' => false,
    ]);

    $product = Product::factory()->create([
        'values' => [
            'common'           => ['test_weight' => '5kg'],
            'locale_specific'  => [],
            'channel_specific' => [],
        ],
    ]);

    $connector = ChannelConnector::create([
        'code'         => 'bidir-common',
        'name'         => 'Bidir Common Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'bidir.myshopify.com'],
        'status'       => 'connected',
        'settings'     => [
            'inbound_strategy' => 'auto_update',
            'default_channel'  => 'default',
            'default_locale'   => 'en_US',
        ],
    ]);

    $mapping = ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'test_weight',
        'channel_field'         => 'weight',
        'direction'             => 'both',
        'sort_order'            => 1,
    ]);

    // Test applyInboundUpdate logic directly
    $webhookPayload = [
        'event' => 'product.updated',
        'id'    => 'ext-123',
        'data'  => [
            'id'     => 'ext-123',
            'weight' => '10kg',
        ],
    ];

    $job = new ProcessWebhookJob($connector->id, $webhookPayload);

    // Call applyInboundUpdate directly via reflection
    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('applyInboundUpdate');

    $mappingRepository = app(ChannelFieldMappingRepository::class);
    $method->invoke($job, $product, $connector, $mappingRepository);

    $product->refresh();
    $values = $product->values;

    expect($values['common']['test_weight'])->toBe('10kg');
});

it('routes locale-specific attribute to locale_specific bucket', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'test_title',
        'type'              => 'text',
        'value_per_locale'  => true,
        'value_per_channel' => false,
    ]);

    $product = Product::factory()->create([
        'values' => [
            'common'           => [],
            'locale_specific'  => ['en_US' => ['test_title' => 'Old Title']],
            'channel_specific' => [],
        ],
    ]);

    $connector = ChannelConnector::create([
        'code'         => 'bidir-locale',
        'name'         => 'Bidir Locale Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'bidir-locale.myshopify.com'],
        'status'       => 'connected',
        'settings'     => [
            'inbound_strategy' => 'auto_update',
            'default_channel'  => 'default',
            'default_locale'   => 'en_US',
        ],
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'test_title',
        'channel_field'         => 'title',
        'direction'             => 'both',
        'sort_order'            => 1,
    ]);

    $webhookPayload = [
        'event' => 'product.updated',
        'id'    => 'ext-456',
        'data'  => [
            'id'    => 'ext-456',
            'title' => 'New Title From Channel',
        ],
    ];

    $job = new ProcessWebhookJob($connector->id, $webhookPayload);
    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('applyInboundUpdate');

    $mappingRepository = app(ChannelFieldMappingRepository::class);
    $method->invoke($job, $product, $connector, $mappingRepository);

    $product->refresh();
    $values = $product->values;

    expect($values['locale_specific']['en_US']['test_title'])->toBe('New Title From Channel');
});

it('routes channel-specific attribute to channel_specific bucket', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'test_price',
        'type'              => 'price',
        'value_per_locale'  => false,
        'value_per_channel' => true,
    ]);

    $product = Product::factory()->create([
        'values' => [
            'common'           => [],
            'locale_specific'  => [],
            'channel_specific' => ['default' => ['test_price' => '99.99']],
        ],
    ]);

    $connector = ChannelConnector::create([
        'code'         => 'bidir-channel',
        'name'         => 'Bidir Channel Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'bidir-channel.myshopify.com'],
        'status'       => 'connected',
        'settings'     => [
            'inbound_strategy' => 'auto_update',
            'default_channel'  => 'default',
            'default_locale'   => 'en_US',
        ],
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'test_price',
        'channel_field'         => 'price',
        'direction'             => 'both',
        'sort_order'            => 1,
    ]);

    $webhookPayload = [
        'event' => 'product.updated',
        'id'    => 'ext-789',
        'data'  => [
            'id'    => 'ext-789',
            'price' => '149.99',
        ],
    ];

    $job = new ProcessWebhookJob($connector->id, $webhookPayload);
    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('applyInboundUpdate');

    $mappingRepository = app(ChannelFieldMappingRepository::class);
    $method->invoke($job, $product, $connector, $mappingRepository);

    $product->refresh();
    $values = $product->values;

    expect($values['channel_specific']['default']['test_price'])->toBe('149.99');
});
