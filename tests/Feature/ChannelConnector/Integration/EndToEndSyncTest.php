<?php

use Illuminate\Support\Facades\Event;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\ChannelConnector\Services\SyncJobManager;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('triggers a sync job via the sync manager', function () {
    // Create all models BEFORE faking events (model events need to fire for tenant_id)
    $connector = ChannelConnector::create([
        'code'         => 'test-shop',
        'name'         => 'Test Shop',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => ['access_token' => 'test-token', 'shop_url' => 'test.myshopify.com'],
        'settings'     => ['sync_direction' => 'outbound'],
    ]);

    // Use SyncJobManager directly instead of via controller (avoids Event::fake issues)
    $syncJobManager = app(SyncJobManager::class);
    $syncJob = $syncJobManager->triggerSync($connector, 'full');

    // Verify sync job created
    expect($syncJob)->not->toBeNull();
    expect($syncJob->sync_type)->toBe('full');
    expect($syncJob->status)->toBe('pending');
    expect($syncJob->tenant_id)->not->toBeNull();
});

it('prevents duplicate sync when one is already running', function () {
    $connector = ChannelConnector::create([
        'code'         => 'dup-test',
        'name'         => 'Dup Test',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => ['access_token' => 'test'],
        'settings'     => ['sync_direction' => 'outbound'],
    ]);

    ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'running-job',
        'sync_type'            => 'full',
        'status'               => 'running',
        'total_products'       => 100,
        'synced_products'      => 50,
        'failed_products'      => 0,
        'started_at'           => now(),
    ]);

    $response = $this->post(route('admin.channel_connector.sync.trigger', $connector->code), [
        'sync_type' => 'full',
    ]);

    // Should redirect with error about existing running job
    $response->assertRedirect();

    // Should not create a second job
    expect(ChannelSyncJob::where('channel_connector_id', $connector->id)->count())->toBe(1);
});
