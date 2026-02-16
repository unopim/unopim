<?php

use Illuminate\Support\Str;
use Webkul\ChannelConnector\Jobs\ProcessSyncJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->connector = ChannelConnector::create([
        'code'         => 'job-test',
        'name'         => 'Job Test Connector',
        'channel_type' => 'salla',
        'credentials'  => ['access_token' => 'test'],
        'status'       => 'connected',
    ]);

    $this->syncJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => (string) Str::uuid(),
        'sync_type'            => 'full',
        'status'               => 'pending',
    ]);
});

it('creates a ProcessSyncJob instance', function () {
    $job = new ProcessSyncJob($this->syncJob->id);

    expect($job)->toBeInstanceOf(ProcessSyncJob::class);
});

it('creates ProcessSyncJob with product IDs', function () {
    $job = new ProcessSyncJob($this->syncJob->id, [1, 2, 3]);

    expect($job)->toBeInstanceOf(ProcessSyncJob::class);
});

it('job has correct retry and timeout settings', function () {
    $job = new ProcessSyncJob($this->syncJob->id);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(30);
    expect($job->timeout)->toBe(7200);
});

it('creates ChannelSyncJob with correct attributes', function () {
    expect($this->syncJob->channel_connector_id)->toBe($this->connector->id);
    expect($this->syncJob->status)->toBe('pending');
    expect($this->syncJob->sync_type)->toBe('full');
    expect($this->syncJob->job_id)->not->toBeNull();
});

it('ChannelSyncJob belongs to connector', function () {
    expect($this->syncJob->connector)->not->toBeNull();
    expect($this->syncJob->connector->id)->toBe($this->connector->id);
});

it('ChannelSyncJob casts error_summary as array', function () {
    $this->syncJob->update([
        'error_summary' => ['Product 1 failed: timeout', 'Product 2 failed: 422'],
    ]);

    $this->syncJob->refresh();

    expect($this->syncJob->error_summary)->toBeArray();
    expect($this->syncJob->error_summary)->toHaveCount(2);
});

it('ChannelSyncJob timestamps are cast to datetime', function () {
    $this->syncJob->update([
        'started_at'   => now(),
        'completed_at' => now(),
    ]);

    $this->syncJob->refresh();

    expect($this->syncJob->started_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($this->syncJob->completed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('ChannelSyncJob can track progress counters', function () {
    $this->syncJob->update([
        'total_products'  => 100,
        'synced_products' => 85,
        'failed_products' => 15,
    ]);

    $this->syncJob->refresh();

    expect($this->syncJob->total_products)->toBe(100);
    expect($this->syncJob->synced_products)->toBe(85);
    expect($this->syncJob->failed_products)->toBe(15);
});

it('ChannelSyncJob supports retry tracking', function () {
    $retryJob = ChannelSyncJob::create([
        'channel_connector_id' => $this->connector->id,
        'job_id'               => (string) Str::uuid(),
        'sync_type'            => 'full',
        'status'               => 'pending',
        'retry_of_id'          => $this->syncJob->id,
    ]);

    expect($retryJob->retryOf->id)->toBe($this->syncJob->id);
    expect($this->syncJob->retries)->toHaveCount(1);
});
