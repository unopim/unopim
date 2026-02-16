<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Webkul\ChannelConnector\Jobs\ProcessSyncJob;
use Webkul\ChannelConnector\Models\ChannelConnector;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('dispatches sync for due connectors', function () {
    Queue::fake();

    $connector = ChannelConnector::create([
        'code'          => 'sched-shop',
        'name'          => 'Scheduled Shop',
        'channel_type'  => 'shopify',
        'credentials'   => ['shop_url' => 'sched.myshopify.com'],
        'status'        => 'connected',
        'sync_schedule' => [
            'enabled'           => true,
            'frequency'         => 'hourly',
            'sync_type'         => 'incremental',
            'last_scheduled_at' => now()->subHours(2)->toIso8601String(),
        ],
    ]);

    Artisan::call('channel-connector:run-scheduled-syncs');

    $connector->refresh();

    expect($connector->sync_schedule['last_scheduled_at'])->not->toBe(
        now()->subHours(2)->toIso8601String()
    );

    Queue::assertPushed(ProcessSyncJob::class);
});

it('skips disabled schedules', function () {
    Queue::fake();

    $originalTimestamp = now()->subHours(2)->toIso8601String();

    $connector = ChannelConnector::create([
        'code'          => 'disabled-shop',
        'name'          => 'Disabled Shop',
        'channel_type'  => 'shopify',
        'credentials'   => ['shop_url' => 'disabled.myshopify.com'],
        'status'        => 'connected',
        'sync_schedule' => [
            'enabled'           => false,
            'frequency'         => 'hourly',
            'sync_type'         => 'incremental',
            'last_scheduled_at' => $originalTimestamp,
        ],
    ]);

    Artisan::call('channel-connector:run-scheduled-syncs');

    $connector->refresh();

    expect($connector->sync_schedule['last_scheduled_at'])->toBe($originalTimestamp);

    Queue::assertNotPushed(ProcessSyncJob::class);
});

it('skips disconnected connectors', function () {
    Queue::fake();

    $originalTimestamp = now()->subHours(2)->toIso8601String();

    $connector = ChannelConnector::create([
        'code'          => 'disconnected-shop',
        'name'          => 'Disconnected Shop',
        'channel_type'  => 'shopify',
        'credentials'   => ['shop_url' => 'disconnected.myshopify.com'],
        'status'        => 'disconnected',
        'sync_schedule' => [
            'enabled'           => true,
            'frequency'         => 'hourly',
            'sync_type'         => 'incremental',
            'last_scheduled_at' => $originalTimestamp,
        ],
    ]);

    Artisan::call('channel-connector:run-scheduled-syncs');

    $connector->refresh();

    expect($connector->sync_schedule['last_scheduled_at'])->toBe($originalTimestamp);

    Queue::assertNotPushed(ProcessSyncJob::class);
});

it('supports dry-run mode', function () {
    Queue::fake();

    $originalTimestamp = now()->subHours(2)->toIso8601String();

    $connector = ChannelConnector::create([
        'code'          => 'dryrun-shop',
        'name'          => 'DryRun Shop',
        'channel_type'  => 'shopify',
        'credentials'   => ['shop_url' => 'dryrun.myshopify.com'],
        'status'        => 'connected',
        'sync_schedule' => [
            'enabled'           => true,
            'frequency'         => 'hourly',
            'sync_type'         => 'incremental',
            'last_scheduled_at' => $originalTimestamp,
        ],
    ]);

    Artisan::call('channel-connector:run-scheduled-syncs', ['--dry-run' => true]);

    $connector->refresh();

    expect($connector->sync_schedule['last_scheduled_at'])->toBe($originalTimestamp);

    Queue::assertNotPushed(ProcessSyncJob::class);
});
