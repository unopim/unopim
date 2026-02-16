<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'acl-test',
        'name'         => 'ACL Test',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => ['access_token' => 'test'],
    ]);

    $this->product = Product::factory()->create();

    $this->syncJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => 'acl-job-001',
        'sync_type'            => 'full',
        'status'               => 'completed',
        'total_products'       => 1,
        'synced_products'      => 1,
        'failed_products'      => 0,
        'started_at'           => now()->subMinute(),
        'completed_at'         => now(),
    ]);
});

it('denies connector index without channel_connector.connectors.view permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->get(route('admin.channel_connector.connectors.index'));

    $response->assertStatus(401);
});

it('denies connector create without channel_connector.connectors.create permission', function () {
    $this->loginWithPermissions('custom', ['channel_connector.connectors.view']);

    $response = $this->post(route('admin.channel_connector.connectors.store'), [
        'code'         => 'unauthorized',
        'name'         => 'Unauthorized',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test'],
    ]);

    $response->assertStatus(401);
});

it('denies connector delete without channel_connector.connectors.delete permission', function () {
    $this->loginWithPermissions('custom', ['channel_connector.connectors.view']);

    $response = $this->delete(route('admin.channel_connector.connectors.destroy', $this->connector->code));

    $response->assertStatus(401);
});

it('denies sync trigger without channel_connector.sync.create permission', function () {
    $this->loginWithPermissions('custom', ['channel_connector.connectors.view']);

    $response = $this->post(route('admin.channel_connector.sync.trigger', $this->connector->code), [
        'sync_type' => 'full',
    ]);

    $response->assertStatus(401);
});

it('denies dashboard access without channel_connector.sync.view permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->get(route('admin.channel_connector.dashboard.index'));

    $response->assertStatus(401);
});

it('allows full access with all channel_connector permissions', function () {
    $this->loginWithPermissions('custom', [
        'channel_connector.connectors.view',
        'channel_connector.connectors.create',
        'channel_connector.connectors.edit',
        'channel_connector.connectors.delete',
        'channel_connector.sync.view',
        'channel_connector.sync.create',
    ]);

    $indexResponse = $this->get(route('admin.channel_connector.connectors.index'));
    $indexResponse->assertSuccessful();

    $dashboardResponse = $this->get(route('admin.channel_connector.dashboard.index'));
    $dashboardResponse->assertSuccessful();
});
