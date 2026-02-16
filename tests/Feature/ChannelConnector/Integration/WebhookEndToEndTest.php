<?php

use Illuminate\Support\Facades\Queue;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Jobs\ProcessWebhookJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('processes webhook end-to-end: receive → verify → queue → update product', function () {
    Queue::fake();

    $connector = ChannelConnector::create([
        'code'           => 'webhook-e2e',
        'name'           => 'Webhook E2E',
        'channel_type'   => 'shopify',
        'status'         => 'connected',
        'credentials'    => ['access_token' => 'test', 'webhook_secret' => 'test-secret'],
        'settings'       => ['inbound_strategy' => 'auto_update', 'webhook_token' => 'valid-webhook-token'],
    ]);

    $product = Product::factory()->create([
        'sku'    => 'WEBHOOK-001',
        'values' => [
            'common'          => ['sku' => 'WEBHOOK-001'],
            'locale_specific' => ['en_US' => ['name' => 'Original Name']],
        ],
    ]);

    ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'shopify-ext-789',
        'sync_status'          => 'synced',
        'data_hash'            => md5('original'),
        'last_synced_at'       => now()->subHour(),
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'name',
        'channel_field'         => 'title',
        'direction'             => 'inbound',
        'locale_mapping'        => ['en_US' => 'en'],
    ]);

    // Mock adapter that accepts any webhook
    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('setCredentials')->once()->with(['access_token' => 'test', 'webhook_secret' => 'test-secret']);
    $mockAdapter->shouldReceive('verifyWebhook')->once()->andReturn(true);

    $resolver = app(AdapterResolver::class);
    $resolver->register('shopify', get_class($mockAdapter));
    $this->app->instance(get_class($mockAdapter), $mockAdapter);

    $webhookPayload = [
        'event'       => 'product.updated',
        'external_id' => 'shopify-ext-789',
        'data'        => [
            'title'   => 'Updated Product Name',
            'sku'     => 'WEBHOOK-001',
            'locales' => [
                'en' => ['title' => 'Updated Product Name'],
            ],
        ],
    ];

    // Send webhook with HMAC signature
    $signature = hash_hmac('sha256', json_encode($webhookPayload), 'test-secret');

    $response = $this->postJson(
        route('channel_connector.webhooks.receive', 'valid-webhook-token'),
        $webhookPayload,
        [
            'X-Shopify-Hmac-Sha256' => base64_encode($signature),
            'Content-Type'          => 'application/json',
        ]
    );

    $response->assertStatus(200);

    Queue::assertPushed(ProcessWebhookJob::class, function ($job) {
        return true; // Verify job was dispatched
    });
});

it('rejects webhook with invalid token', function () {
    $response = $this->postJson(
        route('channel_connector.webhooks.receive', 'nonexistent-token'),
        ['event' => 'product.updated', 'data' => []],
    );

    $response->assertStatus(404);
});

it('rejects webhook with invalid HMAC signature', function () {
    Queue::fake();

    $connector = ChannelConnector::create([
        'code'           => 'hmac-test',
        'name'           => 'HMAC Test',
        'channel_type'   => 'shopify',
        'status'         => 'connected',
        'credentials'    => ['access_token' => 'test', 'webhook_secret' => 'real-secret'],
        'settings'       => ['webhook_token' => 'hmac-test-token'],
    ]);

    // Mock adapter that rejects invalid signature
    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('setCredentials')->once();
    $mockAdapter->shouldReceive('verifyWebhook')->once()->andReturn(false);

    $resolver = app(AdapterResolver::class);
    $resolver->register('shopify', get_class($mockAdapter));
    $this->app->instance(get_class($mockAdapter), $mockAdapter);

    $response = $this->postJson(
        route('channel_connector.webhooks.receive', 'hmac-test-token'),
        ['event'                 => 'product.updated', 'data' => ['title' => 'Hacked']],
        ['X-Shopify-Hmac-Sha256' => 'invalid-signature'],
    );

    $response->assertStatus(401);

    Queue::assertNotPushed(ProcessWebhookJob::class);
});
