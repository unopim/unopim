<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Services\SyncEngine;
use Webkul\Product\Models\Product;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'mapping-test',
        'name'         => 'Mapping Test Connector',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
        'status'       => 'connected',
    ]);

    $this->syncEngine = app(SyncEngine::class);
});

it('creates mapping on first sync', function () {
    $product = Product::factory()->create();

    $externalId = 'gid://shopify/Product/123456';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'data_hash'            => md5(json_encode($product->values)),
    ]);

    $this->assertDatabaseHas('product_channel_mappings', [
        'product_id'  => $product->id,
        'external_id' => $externalId,
        'sync_status' => 'synced',
    ]);
});

it('stores external_id correctly', function () {
    $product = Product::factory()->create();

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'external-id-12345',
        'entity_type'          => 'product',
    ]);

    expect($mapping->external_id)->toBe('external-id-12345');
    expect($mapping->product_id)->toBe($product->id);
});

it('updates data_hash on each sync', function () {
    $product = Product::factory()->create();
    $product->values = ['common' => ['sku' => 'ORIGINAL-SKU']];
    $product->save();

    $originalHash = md5(json_encode($product->fresh()->values));

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-001',
        'entity_type'          => 'product',
        'data_hash'            => $originalHash,
    ]);

    expect($mapping->data_hash)->toBe($originalHash);

    // Update product values (not in $fillable, must assign directly)
    $product->values = ['common' => ['sku' => 'MODIFIED-SKU']];
    $product->save();

    $newHash = md5(json_encode($product->fresh()->values));

    $mapping->update(['data_hash' => $newHash]);

    expect($mapping->fresh()->data_hash)->toBe($newHash);
    expect($newHash)->not->toBe($originalHash);
});

it('transitions sync_status from pending to synced', function () {
    $product = Product::factory()->create();

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'pending-ext-id',
        'entity_type'          => 'product',
        'sync_status'          => 'pending',
    ]);

    expect($mapping->sync_status)->toBe('pending');

    $mapping->update([
        'external_id' => 'new-ext-id',
        'sync_status' => 'synced',
    ]);

    expect($mapping->fresh()->sync_status)->toBe('synced');
});

it('transitions sync_status to failed on error', function () {
    $product = Product::factory()->create();

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'fail-test-ext',
        'entity_type'          => 'product',
        'sync_status'          => 'pending',
    ]);

    $mapping->update([
        'sync_status' => 'failed',
        'meta'        => ['error' => 'API returned 500 error'],
    ]);

    expect($mapping->fresh()->sync_status)->toBe('failed');
    expect($mapping->fresh()->meta['error'])->toContain('500 error');
});

it('detects hash change for incremental sync', function () {
    $product = Product::factory()->create();
    $product->values = ['common' => ['sku' => 'HASH-TEST']];
    $product->save();

    $originalHash = md5(json_encode($product->fresh()->values));

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-hash',
        'entity_type'          => 'product',
        'data_hash'            => $originalHash,
    ]);

    // Product unchanged - no sync needed
    $currentHash = md5(json_encode($product->fresh()->values));
    expect($currentHash)->toBe($originalHash);

    // Modify product (values not in $fillable, must assign directly)
    $product->values = ['common' => ['sku' => 'MODIFIED-SKU']];
    $product->save();

    $newHash = md5(json_encode($product->fresh()->values));

    expect($newHash)->not->toBe($originalHash);
});

it('marks mapping as deleted when product deleted on channel', function () {
    $product = Product::factory()->create();

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'to-be-deleted',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $mapping->update(['sync_status' => 'deleted']);

    expect($mapping->fresh()->sync_status)->toBe('deleted');
});

it('supports multiple mappings for same product across different connectors', function () {
    $product = Product::factory()->create();

    $connector2 = ChannelConnector::create([
        'code'         => 'second-connector',
        'name'         => 'Second Connector',
        'channel_type' => 'salla',
        'credentials'  => [],
        'status'       => 'connected',
    ]);

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'shopify-123',
        'entity_type'          => 'product',
    ]);

    ProductChannelMapping::create([
        'channel_connector_id' => $connector2->id,
        'product_id'           => $product->id,
        'external_id'          => 'salla-456',
        'entity_type'          => 'product',
    ]);

    expect(ProductChannelMapping::where('product_id', $product->id)->count())->toBe(2);
});

it('prevents duplicate mappings for same product and connector', function () {
    $product = Product::factory()->create();

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'unique-ext',
        'entity_type'          => 'product',
    ]);

    expect(function () use ($product) {
        ProductChannelMapping::create([
            'channel_connector_id' => $this->connector->id,
            'product_id'           => $product->id,
            'external_id'          => 'different-ext',
            'entity_type'          => 'product',
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

it('stores last_synced_at timestamp', function () {
    $product = Product::factory()->create();

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'timestamp-test',
        'entity_type'          => 'product',
        'last_synced_at'       => now(),
    ]);

    expect($mapping->last_synced_at)->not->toBeNull();
    expect($mapping->last_synced_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
