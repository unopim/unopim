<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Events\ConflictDetected;
use Webkul\ChannelConnector\Events\ConflictResolved;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;
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
        'code'         => 'edge-case-test',
        'name'         => 'Edge Case Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'edge.myshopify.com'],
        'settings'     => ['conflict_strategy' => 'always_ask'],
        'status'       => 'connected',
    ]);

    $this->syncJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => 'edge-job-'.uniqid(),
        'status'               => 'completed',
        'sync_type'            => 'full',
    ]);

    $this->product = Product::factory()->create([
        'values' => [
            'common' => ['name' => 'PIM Name', 'price' => 99, 'sku' => 'EDGE-001'],
        ],
    ]);

    $this->pcMapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $this->product->id,
        'external_id'          => 'ext-edge-123',
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
            'name'  => ['pim_value' => 'PIM Name', 'channel_value' => 'Channel Name', 'is_locale_specific' => false, 'locales' => []],
            'price' => ['pim_value' => 99, 'channel_value' => 120, 'is_locale_specific' => false, 'locales' => []],
        ],
        'pim_modified_at'     => now()->subMinutes(30),
        'channel_modified_at' => now()->subMinutes(15),
        'resolution_status'   => 'unresolved',
    ]);

    // Create field mappings for import/both directions
    ChannelFieldMapping::create([
        'channel_connector_id'  => $this->connector->id,
        'unopim_attribute_code' => 'name',
        'channel_field'         => 'name',
        'direction'             => 'both',
        'sort_order'            => 0,
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $this->connector->id,
        'unopim_attribute_code' => 'price',
        'channel_field'         => 'price',
        'direction'             => 'both',
        'sort_order'            => 1,
    ]);
});

// ─── Data Integrity Tests ────────────────────────────────────────────

it('pim_wins updates ProductChannelMapping data_hash after sync', function () {
    Event::fake([ConflictResolved::class]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('syncProduct')
        ->once()
        ->andReturn(new SyncResult(
            success: true,
            externalId: 'ext-edge-123',
            action: 'updated',
            dataHash: 'new-pim-hash',
        ));

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')->andReturn($mockAdapter);
    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'pim_wins');

    $this->pcMapping->refresh();

    expect($this->pcMapping->sync_status)->toBe('synced');
    expect($this->pcMapping->last_synced_at)->not->toBeNull();
    // data_hash should have been updated
    expect($this->pcMapping->data_hash)->not->toBe('old-hash');
});

it('channel_wins updates product values in database', function () {
    Event::fake([ConflictResolved::class]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('fetchProduct')
        ->with('ext-edge-123')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Channel Name', 'price' => 120],
            'locales' => [],
        ]);

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')->andReturn($mockAdapter);
    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'channel_wins');

    // Product values should be updated with channel data
    $this->product->refresh();
    expect($this->product->values['common']['name'])->toBe('Channel Name');
    expect($this->product->values['common']['price'])->toBe(120);

    // Mapping hash should be updated
    $this->pcMapping->refresh();
    expect($this->pcMapping->sync_status)->toBe('synced');
});

it('merged applies correct per-field selections to product values', function () {
    Event::fake([ConflictResolved::class]);

    $channelData = [
        'common'  => ['name' => 'Channel Name', 'price' => 120],
        'locales' => [],
    ];

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('fetchProduct')
        ->with('ext-edge-123')
        ->once()
        ->andReturn($channelData);
    $mockAdapter->shouldReceive('syncProduct')
        ->once()
        ->andReturn(new SyncResult(
            success: true,
            externalId: 'ext-edge-123',
            action: 'updated',
            dataHash: 'merged-hash',
        ));

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')->andReturn($mockAdapter);
    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    // name = channel wins, price = pim wins
    $resolver->resolveConflict($this->conflict, 'merged', [
        'name'  => 'channel',
        'price' => 'pim',
    ]);

    // Name should come from channel, price from PIM
    $this->product->refresh();
    expect($this->product->values['common']['name'])->toBe('Channel Name');
    expect($this->product->values['common']['price'])->toBe(99);
});

// ─── Error Handling Tests ────────────────────────────────────────────

it('handles adapter sync failure during pim_wins gracefully', function () {
    Event::fake([ConflictResolved::class]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('syncProduct')
        ->once()
        ->andReturn(new SyncResult(
            success: false,
            action: 'failed',
            errors: ['API error'],
        ));

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')->andReturn($mockAdapter);
    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'pim_wins');

    // Conflict should still be resolved despite sync failure
    $this->conflict->refresh();
    expect($this->conflict->resolution_status)->toBe('pim_wins');

    // But mapping hash should NOT have been updated
    $this->pcMapping->refresh();
    expect($this->pcMapping->data_hash)->toBe('old-hash');
});

it('handles adapter fetch failure during channel_wins gracefully', function () {
    Event::fake([ConflictResolved::class]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('fetchProduct')
        ->with('ext-edge-123')
        ->once()
        ->andReturn(null);

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')->andReturn($mockAdapter);
    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'channel_wins');

    // Conflict still marked as resolved
    $this->conflict->refresh();
    expect($this->conflict->resolution_status)->toBe('channel_wins');

    // But product values should remain unchanged since fetch failed
    $this->product->refresh();
    expect($this->product->values['common']['name'])->toBe('PIM Name');
});

it('handles adapter fetch failure during merged gracefully', function () {
    Event::fake([ConflictResolved::class]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('fetchProduct')
        ->with('ext-edge-123')
        ->once()
        ->andReturn(null);

    $mockAdapterResolver = Mockery::mock(AdapterResolver::class);
    $mockAdapterResolver->shouldReceive('resolve')->andReturn($mockAdapter);
    $this->app->instance(AdapterResolver::class, $mockAdapterResolver);

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $resolver->resolveConflict($this->conflict, 'merged', ['name' => 'channel']);

    $this->conflict->refresh();
    expect($this->conflict->resolution_status)->toBe('merged');

    // Product values should remain unchanged since fetch failed
    $this->product->refresh();
    expect($this->product->values['common']['name'])->toBe('PIM Name');
});

// ─── Edge Cases ──────────────────────────────────────────────────────

it('detects no conflict when channel product was deleted', function () {
    Event::fake([ConflictDetected::class]);

    $adapter = Mockery::mock(ChannelAdapterContract::class);
    $adapter->shouldReceive('fetchProduct')
        ->with('ext-edge-123')
        ->once()
        ->andReturn(null);

    $syncEngine = Mockery::mock(SyncEngine::class);
    $syncEngine->shouldReceive('prepareSyncPayload')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Changed in PIM'],
            'locales' => [],
        ]);
    $syncEngine->shouldReceive('computeDataHash')
        ->andReturn('different-hash');

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $mappings = new Collection;
    $conflict = $resolver->detectConflict($this->product, $this->pcMapping, $adapter, $mappings, $this->syncJob->id);

    expect($conflict)->toBeNull();
    Event::assertNotDispatched(ConflictDetected::class);
});

it('builds conflicting fields with empty locale data', function () {
    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $syncEngine = app(SyncEngine::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $pimValues = [
        'common'  => ['name' => 'PIM', 'price' => 100],
        'locales' => [],
    ];

    $channelValues = [
        'common'  => ['name' => 'Channel', 'price' => 100],
        'locales' => [],
    ];

    $conflicting = $resolver->buildConflictingFields($pimValues, $channelValues, new Collection);

    // Only 'name' differs, 'price' is same
    expect($conflicting)->toHaveKey('name');
    expect($conflicting)->not->toHaveKey('price');
    expect($conflicting['name']['pim_value'])->toBe('PIM');
    expect($conflicting['name']['channel_value'])->toBe('Channel');
    expect($conflicting['name']['is_locale_specific'])->toBeFalse();
    expect($conflicting['name']['locales'])->toBeEmpty();
});
