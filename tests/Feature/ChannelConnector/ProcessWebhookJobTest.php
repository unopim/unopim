<?php

use Webkul\ChannelConnector\Jobs\ProcessWebhookJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'webhook-test',
        'name'         => 'Webhook Test',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => ['inbound_strategy' => 'flag_for_review'],
    ]);
});

it('handles product.deleted event by marking mapping as deleted', function () {
    $product = Product::factory()->create();
    $externalId = 'gid://shopify/Product/999';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.deleted',
        'id'    => $externalId,
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $mapping = ProductChannelMapping::where('external_id', $externalId)->first();
    expect($mapping->sync_status)->toBe('deleted');
});

it('handles product.created event with flag_for_review strategy', function () {
    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.created',
        'id'    => 'gid://shopify/Product/NEW-001',
        'data'  => [
            'title'  => 'New Channel Product',
            'status' => 'active',
        ],
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $conflict = ChannelSyncConflict::where('channel_connector_id', $this->connector->id)
        ->where('conflict_type', 'new_in_channel')
        ->first();

    expect($conflict)->not->toBeNull();
    expect($conflict->resolution_status)->toBe('pending');
});

it('handles product.updated event with flag_for_review strategy', function () {
    $product = Product::factory()->create();
    $externalId = 'gid://shopify/Product/UPDATE-001';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.updated',
        'id'    => $externalId,
        'data'  => [
            'title' => 'Updated Title',
            'price' => '29.99',
        ],
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $conflict = ChannelSyncConflict::where('channel_connector_id', $this->connector->id)
        ->where('product_id', $product->id)
        ->where('conflict_type', 'field_mismatch')
        ->first();

    expect($conflict)->not->toBeNull();
    expect($conflict->resolution_status)->toBe('pending');
    expect($conflict->conflicting_fields)->toHaveKey('title');
    expect($conflict->conflicting_fields)->toHaveKey('price');
});

it('ignores webhook when inbound strategy is ignore', function () {
    $this->connector->update(['settings' => ['inbound_strategy' => 'ignore']]);

    $product = Product::factory()->create();
    $externalId = 'gid://shopify/Product/IGNORE-001';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.updated',
        'id'    => $externalId,
        'data'  => ['title' => 'Should Be Ignored'],
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $conflict = ChannelSyncConflict::where('channel_connector_id', $this->connector->id)
        ->where('product_id', $product->id)
        ->first();

    expect($conflict)->toBeNull();
});

it('skips processing when connector is not found', function () {
    $job = new ProcessWebhookJob(99999, [
        'event' => 'product.updated',
        'id'    => 'ext-001',
    ]);

    // Should not throw
    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    // No conflicts or errors created
    $conflicts = ChannelSyncConflict::count();
    expect($conflicts)->toBe(0);
});

it('handles Shopify-format event types (products/update)', function () {
    $product = Product::factory()->create();
    $externalId = 'gid://shopify/Product/SHOPIFY-FMT';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'type' => 'products/update',
        'id'   => $externalId,
        'data' => ['title' => 'Shopify Format Update'],
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $conflict = ChannelSyncConflict::where('channel_connector_id', $this->connector->id)
        ->where('product_id', $product->id)
        ->first();

    expect($conflict)->not->toBeNull();
    expect($conflict->conflict_type)->toBe('field_mismatch');
});

it('handles products/delete Shopify format event', function () {
    $product = Product::factory()->create();
    $externalId = 'gid://shopify/Product/DEL-SHOPIFY';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'type' => 'products/delete',
        'id'   => $externalId,
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $mapping = ProductChannelMapping::where('external_id', $externalId)->first();
    expect($mapping->sync_status)->toBe('deleted');
});

it('extracts external ID from various payload shapes', function () {
    $product = Product::factory()->create();

    // Shape 1: top-level 'id'
    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-shape-1',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.deleted',
        'id'    => 'ext-shape-1',
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $mapping = ProductChannelMapping::where('external_id', 'ext-shape-1')->first();
    expect($mapping->sync_status)->toBe('deleted');
});

it('extracts external ID from data.id payload shape', function () {
    $product = Product::factory()->create();

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-shape-2',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.deleted',
        'data'  => ['id' => 'ext-shape-2'],
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $mapping = ProductChannelMapping::where('external_id', 'ext-shape-2')->first();
    expect($mapping->sync_status)->toBe('deleted');
});

it('excludes metadata fields from extracted changed fields', function () {
    $product = Product::factory()->create();
    $externalId = 'gid://shopify/Product/META-TEST';

    ProductChannelMapping::create([
        'channel_connector_id' => $this->connector->id,
        'product_id'           => $product->id,
        'external_id'          => $externalId,
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
    ]);

    $job = new ProcessWebhookJob($this->connector->id, [
        'event' => 'product.updated',
        'id'    => $externalId,
        'data'  => [
            'id'                    => $externalId,
            'title'                 => 'Updated Product',
            'created_at'            => '2026-01-01',
            'updated_at'            => '2026-02-16',
            'admin_graphql_api_id'  => 'gid://shopify/Product/999',
        ],
    ]);

    $job->handle(app(\Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository::class));

    $conflict = ChannelSyncConflict::where('product_id', $product->id)->first();
    expect($conflict->conflicting_fields)->toHaveKey('title');
    expect($conflict->conflicting_fields)->not->toHaveKey('id');
    expect($conflict->conflicting_fields)->not->toHaveKey('created_at');
    expect($conflict->conflicting_fields)->not->toHaveKey('updated_at');
    expect($conflict->conflicting_fields)->not->toHaveKey('admin_graphql_api_id');
});
