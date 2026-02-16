<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelFieldMapping;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('returns preview payload for products', function () {
    $connector = ChannelConnector::create([
        'code'         => 'preview-shop',
        'name'         => 'Preview Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'preview.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'sku',
        'channel_field'         => 'sku',
        'direction'             => 'export',
        'sort_order'            => 1,
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'name',
        'channel_field'         => 'title',
        'direction'             => 'export',
        'sort_order'            => 2,
    ]);

    Product::factory()->create(['sku' => 'PREVIEW-001']);

    $response = $this->postJson(route('admin.channel_connector.sync.preview', $connector->code), [
        'sync_type' => 'full',
        'limit'     => 5,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'products',
        'total_available',
        'previewed',
    ]);
    $response->assertJsonPath('previewed', 1);
});

it('respects limit parameter in preview', function () {
    $connector = ChannelConnector::create([
        'code'         => 'limit-shop',
        'name'         => 'Limit Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'limit.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'sku',
        'channel_field'         => 'sku',
        'direction'             => 'export',
        'sort_order'            => 1,
    ]);

    for ($i = 1; $i <= 10; $i++) {
        Product::factory()->create(['sku' => "LIMIT-{$i}"]);
    }

    $response = $this->postJson(route('admin.channel_connector.sync.preview', $connector->code), [
        'sync_type' => 'full',
        'limit'     => 3,
    ]);

    $response->assertOk();
    $response->assertJsonPath('previewed', 3);
});

it('filters by product_codes in preview', function () {
    $connector = ChannelConnector::create([
        'code'         => 'filter-shop',
        'name'         => 'Filter Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'filter.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'sku',
        'channel_field'         => 'sku',
        'direction'             => 'export',
        'sort_order'            => 1,
    ]);

    Product::factory()->create(['sku' => 'FILTER-A']);
    Product::factory()->create(['sku' => 'FILTER-B']);
    Product::factory()->create(['sku' => 'FILTER-C']);

    $response = $this->postJson(route('admin.channel_connector.sync.preview', $connector->code), [
        'sync_type'     => 'full',
        'product_codes' => ['FILTER-A'],
    ]);

    $response->assertOk();
    $response->assertJsonPath('previewed', 1);
});

it('requires sync view permission for preview', function () {
    $this->loginAsAdminWithoutPermissions();

    $connector = ChannelConnector::create([
        'code'         => 'perm-shop',
        'name'         => 'Perm Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'perm.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelFieldMapping::create([
        'channel_connector_id'  => $connector->id,
        'unopim_attribute_code' => 'sku',
        'channel_field'         => 'sku',
        'direction'             => 'export',
        'sort_order'            => 1,
    ]);

    $response = $this->postJson(route('admin.channel_connector.sync.preview', $connector->code));

    $response->assertStatus(401);
});
