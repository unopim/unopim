<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;

it('loads the sync dashboard index page', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'dash-shop',
        'name'         => 'Dashboard Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'dash.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'job-dash-1',
        'status'               => 'completed',
        'sync_type'            => 'full',
        'total_products'       => 50,
        'synced_products'      => 48,
        'failed_products'      => 2,
        'started_at'           => now()->subHour(),
        'completed_at'         => now(),
    ]);

    $response = $this->get(route('admin.channel_connector.dashboard.index'));

    $response->assertOk();
});

it('returns datagrid JSON for ajax requests', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'ajax-shop',
        'name'         => 'Ajax Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'ajax.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'job-ajax-1',
        'status'               => 'completed',
        'sync_type'            => 'full',
        'total_products'       => 10,
        'synced_products'      => 10,
        'failed_products'      => 0,
        'started_at'           => now()->subMinutes(30),
        'completed_at'         => now(),
    ]);

    $response = $this->getJson(route('admin.channel_connector.dashboard.index'));

    $response->assertOk();
});

it('shows job detail page with progress and error summary', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'detail-shop',
        'name'         => 'Detail Shop',
        'channel_type' => 'salla',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'connected',
    ]);

    $job = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'job-detail-1',
        'status'               => 'failed',
        'sync_type'            => 'full',
        'total_products'       => 20,
        'synced_products'      => 17,
        'failed_products'      => 3,
        'error_summary'        => [
            ['product_sku' => 'SKU-001', 'errors' => ['Image URL invalid']],
            ['product_sku' => 'SKU-002', 'errors' => ['Missing required field: title']],
            ['product_sku' => 'SKU-003', 'errors' => ['API rate limit exceeded']],
        ],
        'started_at'   => now()->subMinutes(10),
        'completed_at' => now(),
    ]);

    $response = $this->get(route('admin.channel_connector.dashboard.show', $job->id));

    $response->assertOk();
    $response->assertSee('SKU-001');
    $response->assertSee('SKU-002');
    $response->assertSee('SKU-003');
});

it('returns 404 for non-existent job', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.channel_connector.dashboard.show', 99999));

    $response->assertNotFound();
});

it('filters jobs by status in datagrid', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'filter-shop',
        'name'         => 'Filter Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'filter.myshopify.com'],
        'status'       => 'connected',
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'job-completed',
        'status'               => 'completed',
        'sync_type'            => 'full',
        'total_products'       => 10,
        'synced_products'      => 10,
        'failed_products'      => 0,
        'started_at'           => now()->subHour(),
        'completed_at'         => now(),
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'job-failed',
        'status'               => 'failed',
        'sync_type'            => 'incremental',
        'total_products'       => 5,
        'synced_products'      => 3,
        'failed_products'      => 2,
        'started_at'           => now()->subMinutes(30),
        'completed_at'         => now(),
    ]);

    $response = $this->getJson(route('admin.channel_connector.dashboard.index', [
        'status' => ['failed'],
    ]));

    $response->assertOk();
});

it('enforces ACL for dashboard access', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->get(route('admin.channel_connector.dashboard.index'));

    $response->assertStatus(401);
});

it('shows multiple connector jobs on one dashboard', function () {
    $this->loginAsAdmin();

    $connector1 = ChannelConnector::create([
        'code'         => 'multi-1',
        'name'         => 'Multi Shop 1',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'multi1.myshopify.com'],
        'status'       => 'connected',
    ]);

    $connector2 = ChannelConnector::create([
        'code'         => 'multi-2',
        'name'         => 'Multi Shop 2',
        'channel_type' => 'salla',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'connected',
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector1->id,
        'job_id'               => 'multi-job-1',
        'status'               => 'completed',
        'sync_type'            => 'full',
        'total_products'       => 10,
        'synced_products'      => 10,
        'failed_products'      => 0,
        'started_at'           => now()->subHour(),
        'completed_at'         => now(),
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector2->id,
        'job_id'               => 'multi-job-2',
        'status'               => 'running',
        'sync_type'            => 'incremental',
        'total_products'       => 25,
        'synced_products'      => 12,
        'failed_products'      => 0,
        'started_at'           => now()->subMinutes(5),
    ]);

    $response = $this->getJson(route('admin.channel_connector.dashboard.index'));

    $response->assertOk();
});
