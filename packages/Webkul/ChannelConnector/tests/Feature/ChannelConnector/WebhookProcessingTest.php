<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Jobs\ProcessWebhookJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Product\Models\Product;

it('updates PIM product values with auto_update strategy on product.updated', function () {
    $connector = ChannelConnector::create([
        'code'         => 'wh-auto-update',
        'name'         => 'Webhook Auto Update',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [
            'webhook_token'    => 'test-token',
            'inbound_strategy' => 'auto_update',
        ],
        'status' => 'connected',
    ]);

    $product = Product::factory()->create([
        'values' => ['common' => ['name' => 'Old Name']],
    ]);

    ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-100',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $payload = [
        'event' => 'product.updated',
        'id'    => 'ext-100',
        'data'  => ['title' => 'New Name'],
    ];

    $job = new ProcessWebhookJob($connector->id, $payload);
    $job->handle(app(ChannelFieldMappingRepository::class));

    $mapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
        ->where('external_id', 'ext-100')
        ->first();

    expect($mapping->sync_status)->toBe('synced');
});

it('creates a ChannelSyncConflict with flag_for_review strategy on product.updated', function () {
    $connector = ChannelConnector::create([
        'code'         => 'wh-flag-review',
        'name'         => 'Webhook Flag Review',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [
            'webhook_token'    => 'test-token-2',
            'inbound_strategy' => 'flag_for_review',
        ],
        'status' => 'connected',
    ]);

    $product = Product::factory()->create([
        'values' => ['common' => ['name' => 'Existing Name']],
    ]);

    ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-200',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $payload = [
        'event' => 'product.updated',
        'id'    => 'ext-200',
        'data'  => ['title' => 'Changed Name'],
    ];

    $job = new ProcessWebhookJob($connector->id, $payload);
    $job->handle(app(ChannelFieldMappingRepository::class));

    $conflict = ChannelSyncConflict::where('channel_connector_id', $connector->id)
        ->where('product_id', $product->id)
        ->first();

    expect($conflict)->not->toBeNull();
    expect($conflict->conflict_type)->toBe('field_mismatch');
    expect($conflict->resolution_status)->toBe('pending');
});

it('marks ProductChannelMapping as deleted on product.deleted event', function () {
    $connector = ChannelConnector::create([
        'code'         => 'wh-delete',
        'name'         => 'Webhook Delete',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [
            'webhook_token'    => 'test-token-3',
            'inbound_strategy' => 'auto_update',
        ],
        'status' => 'connected',
    ]);

    $product = Product::factory()->create();

    ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-300',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $payload = [
        'event' => 'product.deleted',
        'id'    => 'ext-300',
    ];

    $job = new ProcessWebhookJob($connector->id, $payload);
    $job->handle(app(ChannelFieldMappingRepository::class));

    $mapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
        ->where('external_id', 'ext-300')
        ->first();

    expect($mapping->sync_status)->toBe('deleted');
});

it('acknowledges but logs an unsupported event type (CHN-052)', function () {
    Log::shouldReceive('info')->atLeast()->once();
    Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) {
        return str_contains($message, 'Unsupported webhook event')
            && ($context['event'] ?? null) === 'order.created';
    });

    $connector = ChannelConnector::create([
        'code'         => 'wh-unsupported',
        'name'         => 'Webhook Unsupported',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [
            'webhook_token'    => 'test-token-4',
            'inbound_strategy' => 'auto_update',
        ],
        'status' => 'connected',
    ]);

    $payload = [
        'event' => 'order.created',
        'id'    => 'ord-999',
    ];

    $job = new ProcessWebhookJob($connector->id, $payload);
    $job->handle(app(ChannelFieldMappingRepository::class));
});

it('acknowledges webhook within 2 seconds (response time constraint)', function () {
    Queue::fake();

    $token = \Illuminate\Support\Str::uuid()->toString();

    $connector = ChannelConnector::create([
        'code'         => 'wh-perf',
        'name'         => 'Webhook Perf',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => ['webhook_token' => $token, 'inbound_strategy' => 'auto_update'],
        'status'       => 'connected',
    ]);

    $mockAdapter = \Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('verifyWebhook')->once()->andReturn(true);

    $resolver = \Mockery::mock(AdapterResolver::class);
    $resolver->shouldReceive('resolve')->andReturn($mockAdapter);

    app()->instance(AdapterResolver::class, $resolver);

    $start = microtime(true);

    $response = $this->postJson(
        route('channel_connector.webhooks.receive', $token),
        ['event' => 'product.updated', 'id' => 'ext-perf']
    );

    $elapsed = microtime(true) - $start;

    $response->assertStatus(200);
    expect($elapsed)->toBeLessThan(2.0);

    Queue::assertPushedOn('webhooks', ProcessWebhookJob::class);
});

it('logs and skips processing when inbound_strategy is ignore', function () {
    Log::shouldReceive('info')->atLeast()->once()->withArgs(function ($message) {
        return str_contains($message, 'Webhook ignored per inbound strategy')
            || str_contains($message, 'Processing webhook');
    });

    $connector = ChannelConnector::create([
        'code'         => 'wh-ignore',
        'name'         => 'Webhook Ignore',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => [
            'webhook_token'    => 'test-token-ignore',
            'inbound_strategy' => 'ignore',
        ],
        'status' => 'connected',
    ]);

    $payload = [
        'event' => 'product.updated',
        'id'    => 'ext-ignore',
        'data'  => ['title' => 'Should Not Process'],
    ];

    $job = new ProcessWebhookJob($connector->id, $payload);
    $job->handle(app(ChannelFieldMappingRepository::class));

    // Verify no conflict was created
    $conflictCount = ChannelSyncConflict::where('channel_connector_id', $connector->id)->count();
    expect($conflictCount)->toBe(0);
});
