<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Webkul\ChannelConnector\Jobs\ProcessSyncJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'sync-trigger',
        'name'         => 'Sync Trigger Test',
        'channel_type' => 'salla',
        'credentials'  => ['access_token' => 'test_token'],
        'status'       => 'connected',
    ]);
});

it('can trigger full sync via admin route', function () {
    Queue::fake();

    $response = $this->post(
        route('admin.channel_connector.sync.trigger', $this->connector->code),
        ['sync_type' => 'full']
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('channel_sync_jobs', [
        'channel_connector_id' => $this->connector->id,
        'sync_type'            => 'full',
        'status'               => 'pending',
    ]);
});

it('can trigger incremental sync via admin route', function () {
    Queue::fake();

    $response = $this->post(
        route('admin.channel_connector.sync.trigger', $this->connector->code),
        ['sync_type' => 'incremental']
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('channel_sync_jobs', [
        'channel_connector_id' => $this->connector->id,
        'sync_type'            => 'incremental',
    ]);
});

it('dispatches ProcessSyncJob on sync trigger', function () {
    Queue::fake();

    $this->post(
        route('admin.channel_connector.sync.trigger', $this->connector->code),
        ['sync_type' => 'full']
    );

    Queue::assertPushed(ProcessSyncJob::class);
});

it('prevents duplicate running jobs', function () {
    ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => (string) Str::uuid(),
        'sync_type'            => 'full',
        'status'               => 'running',
        'started_at'           => now(),
    ]);

    $response = $this->post(
        route('admin.channel_connector.sync.trigger', $this->connector->code),
        ['sync_type' => 'full']
    );

    $response->assertSessionHasErrors();
});

it('creates ChannelSyncJob record on trigger', function () {
    Queue::fake();

    $this->post(
        route('admin.channel_connector.sync.trigger', $this->connector->code),
        ['sync_type' => 'full']
    );

    $syncJob = ChannelSyncJob::where('channel_connector_id', $this->connector->id)->first();

    expect($syncJob)->not->toBeNull();
    expect($syncJob->status)->toBe('pending');
    expect($syncJob->job_id)->not->toBeNull();
});
