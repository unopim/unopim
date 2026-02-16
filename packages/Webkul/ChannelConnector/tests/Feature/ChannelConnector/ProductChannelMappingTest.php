<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ProductChannelMapping;

it('creates product channel mapping', function () {
    $connector = ChannelConnector::create([
        'code'        => 'pcm-test', 'name' => 'PCM', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => 1,
        'external_id'          => 'gid://shopify/Product/123',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'data_hash'            => md5('test'),
    ]);

    expect($mapping->external_id)->toBe('gid://shopify/Product/123');
    expect($mapping->sync_status)->toBe('synced');
    expect($mapping->data_hash)->toBe(md5('test'));
});

it('detects hash change for incremental sync', function () {
    $connector = ChannelConnector::create([
        'code'        => 'hash-test', 'name' => 'Hash', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    $oldHash = md5(json_encode(['sku' => 'old']));
    $newHash = md5(json_encode(['sku' => 'new']));

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => 1,
        'external_id'          => 'ext-1',
        'entity_type'          => 'product',
        'sync_status'          => 'synced',
        'data_hash'            => $oldHash,
    ]);

    expect($mapping->data_hash)->not->toBe($newHash);
});
