<?php

use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Repositories\UnifiedOrderRepository;

beforeEach(function () {
    $this->repository = app(UnifiedOrderRepository::class);
});

it('can find order by id', function () {
    $order = UnifiedOrder::factory()->create();

    $found = $this->repository->find($order->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($order->id);
});

it('can find order by external id', function () {
    $order = UnifiedOrder::factory()->create(['external_id' => 'EXT-123']);

    $found = $this->repository->findByExternalId('EXT-123');

    expect($found)->not->toBeNull()
        ->and($found->external_id)->toBe('EXT-123');
});

it('can find order by order number', function () {
    $order = UnifiedOrder::factory()->create(['order_number' => 'ORD-UNIQUE']);

    $found = $this->repository->findByOrderNumber('ORD-UNIQUE');

    expect($found)->not->toBeNull()
        ->and($found->order_number)->toBe('ORD-UNIQUE');
});

it('can get orders by channel', function () {
    $channel = Channel::factory()->create();

    UnifiedOrder::factory()->count(5)->create(['channel_id' => $channel->id]);
    UnifiedOrder::factory()->count(3)->create();

    $orders = $this->repository->getByChannel($channel->id);

    expect($orders)->toHaveCount(5);
});

it('can get orders by status', function () {
    UnifiedOrder::factory()->count(3)->create(['status' => 'completed']);
    UnifiedOrder::factory()->count(2)->create(['status' => 'pending']);

    $completed = $this->repository->getByStatus('completed');

    expect($completed)->toHaveCount(3);
});

it('can get orders by date range', function () {
    UnifiedOrder::factory()->create(['order_date' => now()->subDays(10)]);
    UnifiedOrder::factory()->create(['order_date' => now()->subDays(5)]);
    UnifiedOrder::factory()->create(['order_date' => now()->subDay()]);

    $orders = $this->repository->getByDateRange(
        now()->subDays(7),
        now()
    );

    expect($orders)->toHaveCount(2);
});

it('can create order', function () {
    $channel = Channel::factory()->create();

    $data = [
        'channel_id' => $channel->id,
        'order_number' => 'TEST-001',
        'status' => 'pending',
        'total_amount' => 100.00,
        'customer_email' => 'test@example.com',
    ];

    $order = $this->repository->create($data);

    expect($order)->toBeInstanceOf(UnifiedOrder::class)
        ->and($order->order_number)->toBe('TEST-001')
        ->and($order->status)->toBe('pending');
});

it('can update order', function () {
    $order = UnifiedOrder::factory()->create(['status' => 'pending']);

    $updated = $this->repository->update(['status' => 'processing'], $order->id);

    expect($updated)->toBeTrue()
        ->and($order->fresh()->status)->toBe('processing');
});

it('can delete order', function () {
    $order = UnifiedOrder::factory()->create();

    $deleted = $this->repository->delete($order->id);

    expect($deleted)->toBeTrue()
        ->and(UnifiedOrder::find($order->id))->toBeNull();
});

it('can get paginated orders', function () {
    UnifiedOrder::factory()->count(25)->create();

    $paginated = $this->repository->paginate(10);

    expect($paginated->total())->toBe(25)
        ->and($paginated->perPage())->toBe(10)
        ->and($paginated->count())->toBe(10);
});

it('can search orders by order number', function () {
    UnifiedOrder::factory()->create(['order_number' => 'ORD-SEARCH-123']);
    UnifiedOrder::factory()->create(['order_number' => 'ORD-OTHER-456']);

    $results = $this->repository->search('SEARCH');

    expect($results)->toHaveCount(1)
        ->and($results->first()->order_number)->toContain('SEARCH');
});

it('can search orders by customer email', function () {
    UnifiedOrder::factory()->create(['customer_email' => 'test@example.com']);
    UnifiedOrder::factory()->create(['customer_email' => 'other@example.com']);

    $results = $this->repository->search('test@example.com');

    expect($results)->toHaveCount(1)
        ->and($results->first()->customer_email)->toBe('test@example.com');
});

it('can get orders with items', function () {
    $order = $this->createOrderWithItems(3);

    $found = $this->repository->findWithItems($order->id);

    expect($found->orderItems)->toBeLoaded()
        ->and($found->orderItems)->toHaveCount(3);
});

it('can count orders by status', function () {
    UnifiedOrder::factory()->count(5)->create(['status' => 'completed']);
    UnifiedOrder::factory()->count(3)->create(['status' => 'pending']);

    $count = $this->repository->countByStatus('completed');

    expect($count)->toBe(5);
});

it('can get recent orders', function () {
    UnifiedOrder::factory()->count(10)->create(['created_at' => now()->subDays(10)]);
    UnifiedOrder::factory()->count(5)->create(['created_at' => now()]);

    $recent = $this->repository->getRecent(7);

    expect($recent)->toHaveCount(5);
});
