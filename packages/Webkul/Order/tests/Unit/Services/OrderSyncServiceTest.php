<?php

use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderSyncLog;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Services\OrderSyncService;
use Webkul\Order\Services\Adapters\SallaOrderAdapter;

beforeEach(function () {
    $this->service = app(OrderSyncService::class);
    $this->channel = Channel::factory()->create(['type' => 'salla']);
});

it('creates sync log before syncing', function () {
    expect(OrderSyncLog::count())->toBe(0);

    $this->mock(SallaOrderAdapter::class, function ($mock) {
        $mock->shouldReceive('fetchOrders')->andReturn([]);
    });

    $this->service->syncChannel($this->channel->id);

    expect(OrderSyncLog::count())->toBeGreaterThanOrEqual(1);
});

it('syncs orders from channel adapter successfully', function () {
    $mockOrders = [
        [
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total_amount' => 100.00,
            'customer_email' => 'test@example.com',
        ],
        [
            'order_number' => 'ORD-002',
            'status' => 'pending',
            'total_amount' => 200.00,
            'customer_email' => 'test2@example.com',
        ],
    ];

    $this->mock(SallaOrderAdapter::class, function ($mock) use ($mockOrders) {
        $mock->shouldReceive('fetchOrders')->andReturn($mockOrders);
    });

    $result = $this->service->syncChannel($this->channel->id);

    expect($result)->toBeArray()
        ->and($result['synced_count'])->toBe(2)
        ->and(UnifiedOrder::count())->toBeGreaterThanOrEqual(2);
});

it('handles sync failures gracefully', function () {
    $this->mock(SallaOrderAdapter::class, function ($mock) {
        $mock->shouldReceive('fetchOrders')->andThrow(new \Exception('Connection timeout'));
    });

    $result = $this->service->syncChannel($this->channel->id);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('failed')
        ->and($result['error'])->toContain('Connection timeout');

    $log = OrderSyncLog::latest()->first();
    expect($log->status)->toBe('failed')
        ->and($log->error_message)->not->toBeNull();
});

it('updates existing orders instead of creating duplicates', function () {
    // Create existing order
    $existingOrder = UnifiedOrder::factory()->create([
        'channel_id' => $this->channel->id,
        'external_id' => 'EXT-123',
        'status' => 'pending',
    ]);

    $mockOrders = [
        [
            'external_id' => 'EXT-123',
            'status' => 'completed',
            'total_amount' => 150.00,
        ],
    ];

    $this->mock(SallaOrderAdapter::class, function ($mock) use ($mockOrders) {
        $mock->shouldReceive('fetchOrders')->andReturn($mockOrders);
    });

    $this->service->syncChannel($this->channel->id);

    expect(UnifiedOrder::where('external_id', 'EXT-123')->count())->toBe(1);

    $updatedOrder = UnifiedOrder::where('external_id', 'EXT-123')->first();
    expect($updatedOrder->status)->toBe('completed')
        ->and($updatedOrder->total_amount)->toBe(150.00);
});

it('syncs orders within date range', function () {
    $fromDate = now()->subDays(7);
    $toDate = now();

    $this->mock(SallaOrderAdapter::class, function ($mock) use ($fromDate, $toDate) {
        $mock->shouldReceive('fetchOrders')
            ->with($fromDate, $toDate)
            ->andReturn([]);
    });

    $result = $this->service->syncChannel($this->channel->id, $fromDate, $toDate);

    expect($result)->toBeArray();
});

it('marks sync log as completed after successful sync', function () {
    $this->mock(SallaOrderAdapter::class, function ($mock) {
        $mock->shouldReceive('fetchOrders')->andReturn([]);
    });

    $this->service->syncChannel($this->channel->id);

    $log = OrderSyncLog::latest()->first();
    expect($log->status)->toBe('completed')
        ->and($log->completed_at)->not->toBeNull();
});

it('tracks sync statistics', function () {
    $mockOrders = array_fill(0, 15, [
        'order_number' => 'ORD-' . rand(1000, 9999),
        'status' => 'completed',
        'total_amount' => 100.00,
    ]);

    $this->mock(SallaOrderAdapter::class, function ($mock) use ($mockOrders) {
        $mock->shouldReceive('fetchOrders')->andReturn($mockOrders);
    });

    $result = $this->service->syncChannel($this->channel->id);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['synced_count', 'created_count', 'updated_count'])
        ->and($result['synced_count'])->toBe(15);
});

it('validates channel exists before syncing', function () {
    expect(fn () => $this->service->syncChannel(99999))
        ->toThrow(\Exception::class);
});

it('supports retry mechanism for failed syncs', function () {
    $log = OrderSyncLog::factory()->create([
        'status' => 'failed',
        'channel_id' => $this->channel->id,
    ]);

    $this->mock(SallaOrderAdapter::class, function ($mock) {
        $mock->shouldReceive('fetchOrders')->andReturn([]);
    });

    $result = $this->service->retrySyncLog($log->id);

    expect($result)->toBeArray()
        ->and($log->fresh()->status)->toBe('completed');
});
