<?php

use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderSyncLog;

it('can create sync log with factory', function () {
    $log = OrderSyncLog::factory()->create();

    expect($log)->toBeInstanceOf(OrderSyncLog::class)
        ->and($log->id)->not->toBeNull()
        ->and($log->tenant_id)->not->toBeNull();
});

it('belongs to channel', function () {
    $channel = Channel::factory()->create();
    $log = OrderSyncLog::factory()->create(['channel_id' => $channel->id]);

    expect($log->channel)->toBeInstanceOf(Channel::class)
        ->and($log->channel->id)->toBe($channel->id);
});

it('scopes logs by channel', function () {
    $channel1 = Channel::factory()->create();
    $channel2 = Channel::factory()->create();

    OrderSyncLog::factory()->count(3)->create(['channel_id' => $channel1->id]);
    OrderSyncLog::factory()->count(2)->create(['channel_id' => $channel2->id]);

    $logs = OrderSyncLog::byChannel($channel1->id)->get();

    expect($logs)->toHaveCount(3);
});

it('scopes logs by status', function () {
    OrderSyncLog::factory()->count(3)->create(['status' => 'completed']);
    OrderSyncLog::factory()->count(2)->create(['status' => 'failed']);
    OrderSyncLog::factory()->count(1)->create(['status' => 'running']);

    $completed = OrderSyncLog::byStatus('completed')->get();
    $failed = OrderSyncLog::byStatus('failed')->get();

    expect($completed)->toHaveCount(3)
        ->and($failed)->toHaveCount(2);
});

it('scopes logs by resource type', function () {
    OrderSyncLog::factory()->count(5)->create(['resource_type' => 'order']);
    OrderSyncLog::factory()->count(2)->create(['resource_type' => 'product']);

    $orderLogs = OrderSyncLog::byResourceType('order')->get();

    expect($orderLogs)->toHaveCount(5);
});

it('scopes recent logs', function () {
    OrderSyncLog::factory()->create(['created_at' => now()->subDays(10)]);
    OrderSyncLog::factory()->create(['created_at' => now()->subDays(5)]);
    OrderSyncLog::factory()->create(['created_at' => now()->subDay()]);

    $recentLogs = OrderSyncLog::recent(7)->get();

    expect($recentLogs)->toHaveCount(2);
});

it('marks log as completed', function () {
    $log = OrderSyncLog::factory()->create(['status' => 'running']);

    $log->markAsCompleted();

    expect($log->status)->toBe('completed')
        ->and($log->completed_at)->not->toBeNull();
});

it('marks log as failed with error', function () {
    $log = OrderSyncLog::factory()->create(['status' => 'running']);
    $errorMessage = 'Connection timeout';

    $log->markAsFailed($errorMessage);

    expect($log->status)->toBe('failed')
        ->and($log->error_message)->toBe($errorMessage)
        ->and($log->failed_at)->not->toBeNull();
});

it('has fillable attributes', function () {
    $log = new OrderSyncLog();

    expect($log->getFillable())->toBeArray()
        ->and($log->getFillable())->toContain('channel_id', 'status', 'resource_type', 'resource_id');
});

it('casts attributes correctly', function () {
    $log = OrderSyncLog::factory()->create([
        'started_at' => '2024-01-15 10:00:00',
        'completed_at' => '2024-01-15 10:05:00',
        'metadata' => ['synced_count' => 10],
    ]);

    expect($log->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($log->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($log->metadata)->toBeArray();
});

it('calculates duration correctly', function () {
    $log = OrderSyncLog::factory()->create([
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
    ]);

    $duration = $log->getDuration();

    expect($duration)->toBeGreaterThan(290) // ~5 minutes in seconds
        ->and($duration)->toBeLessThan(310);
});

it('returns null duration for incomplete sync', function () {
    $log = OrderSyncLog::factory()->create([
        'started_at' => now()->subMinutes(5),
        'completed_at' => null,
        'status' => 'running',
    ]);

    expect($log->getDuration())->toBeNull();
});
