<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;

it('can trigger sync job', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'        => 'sync-trigger', 'name' => 'Sync Test', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    $response = $this->post(route('admin.channel_connector.sync.trigger', $connector->code), [
        'sync_type' => 'full',
    ]);

    $response->assertRedirect();
    expect(ChannelSyncJob::where('channel_connector_id', $connector->id)->count())->toBeGreaterThanOrEqual(1);
});

it('prevents duplicate running sync jobs', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'        => 'dup-sync', 'name' => 'Dup Sync', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => \Illuminate\Support\Str::uuid()->toString(),
        'status'               => 'running', 'sync_type' => 'full',
    ]);

    $response = $this->post(route('admin.channel_connector.sync.trigger', $connector->code), [
        'sync_type' => 'incremental',
    ]);

    $response->assertSessionHasErrors();
});

it('validates sync_type parameter', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'        => 'val-sync', 'name' => 'Val Sync', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    $response = $this->post(route('admin.channel_connector.sync.trigger', $connector->code), [
        'sync_type' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['sync_type']);
});
