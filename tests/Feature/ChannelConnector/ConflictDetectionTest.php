<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Events\ConflictDetected;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Services\ConflictResolver;
use Webkul\ChannelConnector\Services\SyncEngine;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'conflict-detect-test',
        'name'         => 'Conflict Detection Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'test.myshopify.com'],
        'settings'     => ['conflict_strategy' => 'always_ask'],
        'status'       => 'connected',
    ]);

    $this->syncJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => 'test-job-'.uniqid(),
        'status'               => 'running',
        'sync_type'            => 'full',
    ]);
});

it('detects conflict when both PIM hash and channel hash differ from stored hash', function () {
    Event::fake([ConflictDetected::class]);

    $product = Product::factory()->create([
        'values' => [
            'common' => ['name' => 'Updated in PIM'],
        ],
    ]);

    $storedHash = md5(json_encode(['common' => ['name' => 'Original Value'], 'locales' => []]));

    $pcMapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-123',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'last_synced_at'       => now()->subHour(),
        'data_hash'            => $storedHash,
    ]);

    // Mock adapter returns different data (channel also modified)
    $adapter = Mockery::mock(ChannelAdapterContract::class);
    $adapter->shouldReceive('fetchProduct')
        ->with('ext-123')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Updated in Channel'],
            'locales' => [],
        ]);

    // Mock SyncEngine to return PIM payload different from stored hash
    $syncEngine = Mockery::mock(SyncEngine::class);
    $syncEngine->shouldReceive('prepareSyncPayload')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Updated in PIM'],
            'locales' => [],
        ]);

    $pimHash = md5(json_encode(['common' => ['name' => 'Updated in PIM'], 'locales' => []]));
    $channelHash = md5(json_encode(['common' => ['name' => 'Updated in Channel'], 'locales' => []]));

    $syncEngine->shouldReceive('computeDataHash')
        ->andReturnUsing(function (array $payload) {
            ksort($payload);

            return md5(json_encode($payload));
        });

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $mappings = new Collection;
    $conflict = $resolver->detectConflict($product, $pcMapping, $adapter, $mappings, $this->syncJob->id);

    expect($conflict)->toBeInstanceOf(ChannelSyncConflict::class);
    expect($conflict->conflict_type)->toBe('both_modified');
    expect($conflict->resolution_status)->toBe('unresolved');
    expect($conflict->product_id)->toBe($product->id);
    expect($conflict->channel_connector_id)->toBe($this->connector->id);

    Event::assertDispatched(ConflictDetected::class);
});

it('does not detect conflict when only PIM changed (normal sync proceeds)', function () {
    Event::fake([ConflictDetected::class]);

    $product = Product::factory()->create([
        'values' => [
            'common' => ['name' => 'Updated in PIM'],
        ],
    ]);

    $storedHash = md5(json_encode(['common' => ['name' => 'Original'], 'locales' => []]));

    $pcMapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-456',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'last_synced_at'       => now()->subHour(),
        'data_hash'            => $storedHash,
    ]);

    // Channel data matches stored hash (channel did NOT change)
    $adapter = Mockery::mock(ChannelAdapterContract::class);
    $adapter->shouldReceive('fetchProduct')
        ->with('ext-456')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Original'],
            'locales' => [],
        ]);

    $syncEngine = Mockery::mock(SyncEngine::class);
    $syncEngine->shouldReceive('prepareSyncPayload')
        ->once()
        ->andReturn([
            'common'  => ['name' => 'Updated in PIM'],
            'locales' => [],
        ]);

    $syncEngine->shouldReceive('computeDataHash')
        ->andReturnUsing(function (array $payload) {
            ksort($payload);

            return md5(json_encode($payload));
        });

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $mappings = new Collection;
    $conflict = $resolver->detectConflict($product, $pcMapping, $adapter, $mappings, $this->syncJob->id);

    expect($conflict)->toBeNull();
    Event::assertNotDispatched(ConflictDetected::class);
});

it('does not detect conflict when hashes match (skip sync)', function () {
    Event::fake([ConflictDetected::class]);

    $product = Product::factory()->create([
        'values' => [
            'common' => ['name' => 'Unchanged'],
        ],
    ]);

    $payload = ['common' => ['name' => 'Unchanged'], 'locales' => []];
    $storedHash = md5(json_encode($payload));

    $pcMapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-789',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'last_synced_at'       => now()->subHour(),
        'data_hash'            => $storedHash,
    ]);

    // PIM hash matches stored hash — no changes detected
    $syncEngine = Mockery::mock(SyncEngine::class);
    $syncEngine->shouldReceive('prepareSyncPayload')
        ->once()
        ->andReturn($payload);
    $syncEngine->shouldReceive('computeDataHash')
        ->once()
        ->andReturn($storedHash);

    $adapter = Mockery::mock(ChannelAdapterContract::class);
    // fetchProduct should NOT be called since PIM hash matches
    $adapter->shouldNotReceive('fetchProduct');

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $mappings = new Collection;
    $conflict = $resolver->detectConflict($product, $pcMapping, $adapter, $mappings, $this->syncJob->id);

    expect($conflict)->toBeNull();
    Event::assertNotDispatched(ConflictDetected::class);
});

it('includes per-locale values in conflicting_fields structure', function () {
    Event::fake([ConflictDetected::class]);

    $product = Product::factory()->create([
        'values' => [
            'common'          => ['price' => 100],
            'locale_specific' => ['en_US' => ['title' => 'PIM English'], 'fr_FR' => ['title' => 'PIM French']],
        ],
    ]);

    $storedHash = 'stored-hash-different';

    $pcMapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-locale',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'last_synced_at'       => now()->subHour(),
        'data_hash'            => $storedHash,
    ]);

    $pimPayload = [
        'common'  => ['price' => 100],
        'locales' => [
            'en' => ['title' => 'PIM English'],
            'fr' => ['title' => 'PIM French'],
        ],
    ];

    $channelData = [
        'common'  => ['price' => 120],
        'locales' => [
            'en' => ['title' => 'Channel English'],
            'fr' => ['title' => 'Channel French'],
        ],
    ];

    $adapter = Mockery::mock(ChannelAdapterContract::class);
    $adapter->shouldReceive('fetchProduct')
        ->with('ext-locale')
        ->once()
        ->andReturn($channelData);

    $syncEngine = Mockery::mock(SyncEngine::class);
    $syncEngine->shouldReceive('prepareSyncPayload')
        ->once()
        ->andReturn($pimPayload);

    // PIM hash != stored hash, channel hash != stored hash
    $syncEngine->shouldReceive('computeDataHash')
        ->andReturnUsing(function (array $payload) {
            return md5(json_encode($payload));
        });

    $conflictRepo = app(\Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository::class);
    $resolver = new ConflictResolver($conflictRepo, $syncEngine);

    $mappings = new Collection;
    $conflict = $resolver->detectConflict($product, $pcMapping, $adapter, $mappings, $this->syncJob->id);

    expect($conflict)->toBeInstanceOf(ChannelSyncConflict::class);
    expect($conflict->conflicting_fields)->toBeArray();

    // Common field 'price' should be in conflicting fields
    expect($conflict->conflicting_fields)->toHaveKey('price');
    expect($conflict->conflicting_fields['price']['pim_value'])->toBe(100);
    expect($conflict->conflicting_fields['price']['channel_value'])->toBe(120);
    expect($conflict->conflicting_fields['price']['is_locale_specific'])->toBeFalse();

    // Locale-specific field 'title' should have per-locale values
    expect($conflict->conflicting_fields)->toHaveKey('title');
    expect($conflict->conflicting_fields['title']['is_locale_specific'])->toBeTrue();
    expect($conflict->conflicting_fields['title']['locales'])->toHaveKey('en');
    expect($conflict->conflicting_fields['title']['locales']['en']['pim_value'])->toBe('PIM English');
    expect($conflict->conflicting_fields['title']['locales']['en']['channel_value'])->toBe('Channel English');
    expect($conflict->conflicting_fields['title']['locales'])->toHaveKey('fr');
    expect($conflict->conflicting_fields['title']['locales']['fr']['pim_value'])->toBe('PIM French');
    expect($conflict->conflicting_fields['title']['locales']['fr']['channel_value'])->toBe('Channel French');

    Event::assertDispatched(ConflictDetected::class);
});
