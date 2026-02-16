<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;

it('creates sync job with correct status', function () {
    $connector = ChannelConnector::create([
        'code'        => 'job-test', 'name' => 'Job Test', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    $job = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => \Illuminate\Support\Str::uuid()->toString(),
        'status'               => 'pending', 'sync_type' => 'full',
    ]);

    expect($job->status)->toBe('pending');
    expect($job->sync_type)->toBe('full');
});

it('tracks sync progress counters', function () {
    $connector = ChannelConnector::create([
        'code'        => 'progress', 'name' => 'Progress', 'channel_type' => 'shopify',
        'credentials' => encrypt(json_encode([])), 'status' => 'connected',
    ]);

    $job = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => \Illuminate\Support\Str::uuid()->toString(),
        'status'               => 'running', 'sync_type' => 'full',
        'total_products'       => 100, 'synced_products' => 50, 'failed_products' => 5,
    ]);

    expect($job->total_products)->toBe(100);
    expect($job->synced_products)->toBe(50);
    expect($job->failed_products)->toBe(5);
});
