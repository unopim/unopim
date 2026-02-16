<?php

use Illuminate\Support\Facades\Event;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Events\ConflictResolved;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\ChannelConnector\Services\ConflictResolver;
use Webkul\ChannelConnector\Services\SyncEngine;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'conflict-resolve-test',
        'name'         => 'Conflict Resolution Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'resolve.myshopify.com'],
        'settings'     => ['conflict_strategy' => 'always_ask'],
        'status'       => 'connected',
    ]);

    $this->syncJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => 'resolve-job-'.uniqid(),
        'status'               => 'completed',
        'sync_type'            => 'full',
    ]);

    $this->product = Product::factory()->create([
        'values' => [
            'common' => ['name' => 'PIM Product Name', 'price' => 99],
        ],
    ]);

    $this->pcMapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $this->product->id,
        'external_id'          => 'ext-resolve-123',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'last_synced_at'       => now()->subHour(),
        'data_hash'            => 'old-hash',
    ]);

    $this->conflict = ChannelSyncConflict::create([
        'channel_connector_id' => $this->connector->id,
        'channel_sync_job_id'  => $this->syncJob->id,
        'product_id'           => $this->product->id,
        'conflict_type'        => 'both_modified',
        'conflicting_fields'   => [
            'name' => [
                'pim_value'          => 'PIM Product Name',
                'channel_value'      => 'Channel Product Name',
                'is_locale_specific' => false,
                'locales'            => [],
            ],
            'price' => [
                'pim_value'          => 99,
                'channel_value'      => 120,
                'is_locale_specific' => false,
                'locales'            => [],
            ],
        ],
        'pim_modified_at'     => now()->subMinutes(30),
        'channel_modified_at' => now()->subMinutes(15),
        'resolution_status'   => 'unresolved',
    ]);
});

it('resolves with pim_wins by re-syncing PIM values to channel', function () {
    Event::fake([ConflictResolved::class]);

    // Mock adapter for PIM wins push
    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('syncProduct')
        ->once()
        ->andReturn(new SyncResult(
            success: true,
            externalId: 'ext-resolve-123',
            action: 'updated',
            dataHash: 'new-pim-hash',
        ));

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')
        ->andReturn($mockAdapter);

    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'pim_wins');

    $this->conflict->refresh();

    expect($this->conflict->resolution_status)->toBe('pim_wins');
    expect($this->conflict->resolved_at)->not->toBeNull();
    expect($this->conflict->resolution_details)->toBeArray();
    expect($this->conflict->resolution_details['strategy'])->toBe('pim_wins');

    Event::assertDispatched(ConflictResolved::class);
});

it('resolves with channel_wins by pulling channel values into PIM', function () {
    Event::fake([ConflictResolved::class]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('fetchProduct')
        ->with('ext-resolve-123')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Channel Product Name', 'price' => 120],
            'locales' => [],
        ]);

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')
        ->andReturn($mockAdapter);

    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'channel_wins');

    $this->conflict->refresh();

    expect($this->conflict->resolution_status)->toBe('channel_wins');
    expect($this->conflict->resolved_at)->not->toBeNull();
    expect($this->conflict->resolution_details['strategy'])->toBe('channel_wins');

    Event::assertDispatched(ConflictResolved::class);
});

it('resolves with merged by applying per-field overrides', function () {
    Event::fake([ConflictResolved::class]);

    $channelData = [
        'common'  => ['name' => 'Channel Product Name', 'price' => 120],
        'locales' => [],
    ];

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('fetchProduct')
        ->with('ext-resolve-123')
        ->once()
        ->andReturn($channelData);
    $mockAdapter->shouldReceive('syncProduct')
        ->once()
        ->andReturn(new SyncResult(
            success: true,
            externalId: 'ext-resolve-123',
            action: 'updated',
            dataHash: 'merged-hash',
        ));

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')
        ->andReturn($mockAdapter);

    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    // name = channel wins, price = pim wins
    $fieldOverrides = [
        'name'  => 'channel',
        'price' => 'pim',
    ];

    $resolver->resolveConflict($this->conflict, 'merged', $fieldOverrides);

    $this->conflict->refresh();

    expect($this->conflict->resolution_status)->toBe('merged');
    expect($this->conflict->resolved_at)->not->toBeNull();
    expect($this->conflict->resolution_details['strategy'])->toBe('merged');
    expect($this->conflict->resolution_details['field_overrides'])->toBe($fieldOverrides);

    Event::assertDispatched(ConflictResolved::class);
});

it('resolves with dismissed without making any changes', function () {
    Event::fake([ConflictResolved::class]);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'dismissed');

    $this->conflict->refresh();

    expect($this->conflict->resolution_status)->toBe('dismissed');
    expect($this->conflict->resolved_at)->not->toBeNull();
    expect($this->conflict->resolution_details['strategy'])->toBe('dismissed');
    expect($this->conflict->resolution_details['action'])->toBe('conflict_dismissed_no_changes');

    // Product values should remain unchanged
    $this->product->refresh();
    expect($this->product->values['common']['name'])->toBe('PIM Product Name');
    expect($this->product->values['common']['price'])->toBe(99);

    Event::assertDispatched(ConflictResolved::class);
});

it('sets resolved_by and resolved_at correctly', function () {
    Event::fake([ConflictResolved::class]);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'dismissed');

    $this->conflict->refresh();

    expect($this->conflict->resolved_by)->toBe(auth()->guard('admin')->id());
    expect($this->conflict->resolved_at)->not->toBeNull();
    expect($this->conflict->resolved_at->diffInMinutes(now()))->toBeLessThan(1);
});
