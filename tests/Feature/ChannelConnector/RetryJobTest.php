<?php

use Illuminate\Support\Facades\Queue;
use Webkul\ChannelConnector\Jobs\ProcessSyncJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;

it('creates a new retry job linked via retry_of_id', function () {
    Queue::fake();
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'retry-link',
        'name'         => 'Retry Link Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'retry.myshopify.com'],
        'status'       => 'connected',
    ]);

    $originalJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'original-job-1',
        'status'               => 'failed',
        'sync_type'            => 'full',
        'total_products'       => 10,
        'synced_products'      => 7,
        'failed_products'      => 3,
        'error_summary'        => [
            ['product_sku' => 'SKU-A', 'errors' => ['API timeout']],
            ['product_sku' => 'SKU-B', 'errors' => ['Rate limit']],
            ['product_sku' => 'SKU-C', 'errors' => ['Invalid image']],
        ],
        'started_at'   => now()->subMinutes(15),
        'completed_at' => now()->subMinutes(5),
    ]);

    $response = $this->post(route('admin.channel_connector.dashboard.retry', $originalJob->id));

    $response->assertRedirect();

    $retryJob = ChannelSyncJob::where('retry_of_id', $originalJob->id)->first();

    expect($retryJob)->not->toBeNull();
    expect($retryJob->retry_of_id)->toBe($originalJob->id);
    expect($retryJob->status)->toBe('pending');
    expect($retryJob->sync_type)->toBe($originalJob->sync_type);
    expect($retryJob->channel_connector_id)->toBe($originalJob->channel_connector_id);
});

it('transitions original job to retrying status', function () {
    Queue::fake();
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'retry-status',
        'name'         => 'Retry Status Shop',
        'channel_type' => 'salla',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'connected',
    ]);

    $originalJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'status-job-1',
        'status'               => 'failed',
        'sync_type'            => 'incremental',
        'total_products'       => 5,
        'synced_products'      => 3,
        'failed_products'      => 2,
        'error_summary'        => [
            ['product_sku' => 'SKU-X', 'errors' => ['Connection refused']],
            ['product_sku' => 'SKU-Y', 'errors' => ['Timeout']],
        ],
        'started_at'   => now()->subMinutes(10),
        'completed_at' => now(),
    ]);

    $this->post(route('admin.channel_connector.dashboard.retry', $originalJob->id));

    $originalJob->refresh();
    expect($originalJob->status)->toBe('retrying');
});

it('retry job dispatches ProcessSyncJob to queue', function () {
    Queue::fake();
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'retry-queue',
        'name'         => 'Retry Queue Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'queue.myshopify.com'],
        'status'       => 'connected',
    ]);

    $originalJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'queue-job-1',
        'status'               => 'failed',
        'sync_type'            => 'full',
        'total_products'       => 8,
        'synced_products'      => 5,
        'failed_products'      => 3,
        'error_summary'        => [
            ['product_sku' => 'SKU-Q1', 'errors' => ['Error 1']],
            ['product_sku' => 'SKU-Q2', 'errors' => ['Error 2']],
            ['product_sku' => 'SKU-Q3', 'errors' => ['Error 3']],
        ],
        'started_at'   => now()->subMinutes(20),
        'completed_at' => now()->subMinutes(10),
    ]);

    $this->post(route('admin.channel_connector.dashboard.retry', $originalJob->id));

    Queue::assertPushed(ProcessSyncJob::class);
});

it('only allows retrying failed jobs', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'retry-guard',
        'name'         => 'Retry Guard Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'guard.myshopify.com'],
        'status'       => 'connected',
    ]);

    $completedJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'completed-guard',
        'status'               => 'completed',
        'sync_type'            => 'full',
        'total_products'       => 10,
        'synced_products'      => 10,
        'failed_products'      => 0,
        'started_at'           => now()->subHour(),
        'completed_at'         => now(),
    ]);

    $response = $this->post(route('admin.channel_connector.dashboard.retry', $completedJob->id));

    $response->assertRedirect();
    $response->assertSessionHasErrors('retry');

    expect(ChannelSyncJob::where('retry_of_id', $completedJob->id)->exists())->toBeFalse();
});

it('cannot retry a running job', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'retry-running',
        'name'         => 'Retry Running Shop',
        'channel_type' => 'salla',
        'credentials'  => ['api_key' => 'test'],
        'status'       => 'connected',
    ]);

    $runningJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'running-job',
        'status'               => 'running',
        'sync_type'            => 'incremental',
        'total_products'       => 20,
        'synced_products'      => 10,
        'failed_products'      => 0,
        'started_at'           => now()->subMinutes(5),
    ]);

    $response = $this->post(route('admin.channel_connector.dashboard.retry', $runningJob->id));

    $response->assertRedirect();
    $response->assertSessionHasErrors('retry');
});

it('cannot retry a pending job', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'retry-pending',
        'name'         => 'Retry Pending Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'pending.myshopify.com'],
        'status'       => 'connected',
    ]);

    $pendingJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'pending-job',
        'status'               => 'pending',
        'sync_type'            => 'full',
    ]);

    $response = $this->post(route('admin.channel_connector.dashboard.retry', $pendingJob->id));

    $response->assertRedirect();
    $response->assertSessionHasErrors('retry');
});

it('returns 404 when retrying non-existent job', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.channel_connector.dashboard.retry', 99999));

    $response->assertNotFound();
});

it('enforces ACL for retry action', function () {
    $this->loginWithPermissions('custom', ['channel_connector.sync.view']);

    $connector = ChannelConnector::create([
        'code'         => 'acl-retry',
        'name'         => 'ACL Retry Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'acl.myshopify.com'],
        'status'       => 'connected',
    ]);

    $failedJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'acl-failed-job',
        'status'               => 'failed',
        'sync_type'            => 'full',
        'total_products'       => 5,
        'synced_products'      => 2,
        'failed_products'      => 3,
        'error_summary'        => [
            ['product_sku' => 'ACL-1', 'errors' => ['Error']],
        ],
        'started_at'   => now()->subMinutes(10),
        'completed_at' => now(),
    ]);

    $response = $this->post(route('admin.channel_connector.dashboard.retry', $failedJob->id));

    $response->assertStatus(401);
});

it('retry API endpoint returns 202 with job details', function () {
    Queue::fake();
    $token = $this->createAdminApiToken();

    $connector = ChannelConnector::create([
        'code'         => 'api-retry',
        'name'         => 'API Retry Shop',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'apiretry.myshopify.com'],
        'status'       => 'connected',
    ]);

    $failedJob = ChannelSyncJob::create([
        'channel_connector_id' => $connector->id,
        'job_id'               => 'api-failed-job',
        'status'               => 'failed',
        'sync_type'            => 'full',
        'total_products'       => 10,
        'synced_products'      => 7,
        'failed_products'      => 3,
        'error_summary'        => [
            ['product_sku' => 'API-1', 'errors' => ['Error']],
            ['product_sku' => 'API-2', 'errors' => ['Error']],
            ['product_sku' => 'API-3', 'errors' => ['Error']],
        ],
        'started_at'   => now()->subMinutes(15),
        'completed_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->postJson(route('admin.api.channel_connector.sync.retry', [
            'code'  => $connector->code,
            'jobId' => $failedJob->job_id,
        ]));

    $response->assertStatus(202)
        ->assertJsonStructure(['job_id', 'status', 'retry_of', 'products_to_retry']);
});
