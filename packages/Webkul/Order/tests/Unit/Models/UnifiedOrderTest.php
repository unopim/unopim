<?php

use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderSyncLog;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;

it('can create order with factory', function () {
    $order = UnifiedOrder::factory()->create();

    expect($order)->toBeInstanceOf(UnifiedOrder::class)
        ->and($order->id)->not->toBeNull()
        ->and($order->tenant_id)->not->toBeNull()
        ->and($order->order_number)->not->toBeNull();
});

it('has order items relationship', function () {
    $order = UnifiedOrder::factory()->create();
    $items = UnifiedOrderItem::factory()->count(3)->for($order)->create();

    $order->refresh();

    expect($order->orderItems)->toHaveCount(3)
        ->and($order->orderItems->first())->toBeInstanceOf(UnifiedOrderItem::class)
        ->and($order->orderItems->pluck('id')->toArray())->toBe($items->pluck('id')->toArray());
});

it('has channel relationship', function () {
    $channel = Channel::factory()->create();
    $order = UnifiedOrder::factory()->create(['channel_id' => $channel->id]);

    expect($order->channel)->toBeInstanceOf(Channel::class)
        ->and($order->channel->id)->toBe($channel->id);
});

it('has sync logs relationship', function () {
    $order = UnifiedOrder::factory()->create();
    $logs = OrderSyncLog::factory()->count(2)->create([
        'resource_type' => 'order',
        'resource_id' => $order->id,
    ]);

    $order->refresh();

    expect($order->syncLogs)->toHaveCount(2)
        ->and($order->syncLogs->first())->toBeInstanceOf(OrderSyncLog::class);
});

it('calculates profitability correctly', function () {
    $order = UnifiedOrder::factory()->create(['total_amount' => 1000.00]);

    UnifiedOrderItem::factory()->create([
        'unified_order_id' => $order->id,
        'price' => 500.00,
        'quantity' => 2,
        'cost_basis' => 300.00,
    ]);

    $profitability = $order->calculateProfitability();

    expect($profitability)->toBeArray()
        ->and($profitability)->toHaveKeys(['total_profit', 'margin_percentage', 'total_revenue', 'total_cost'])
        ->and($profitability['total_revenue'])->toBe(1000.00)
        ->and($profitability['total_cost'])->toBe(600.00)
        ->and($profitability['total_profit'])->toBe(400.00)
        ->and($profitability['margin_percentage'])->toBe(40.00);
});

it('calculates zero profitability for orders without items', function () {
    $order = UnifiedOrder::factory()->create(['total_amount' => 1000.00]);

    $profitability = $order->calculateProfitability();

    expect($profitability)->toBeArray()
        ->and($profitability['total_profit'])->toBe(0.00)
        ->and($profitability['margin_percentage'])->toBe(0.00);
});

it('scopes orders by channel', function () {
    $channel1 = Channel::factory()->create();
    $channel2 = Channel::factory()->create();

    UnifiedOrder::factory()->count(5)->create(['channel_id' => $channel1->id]);
    UnifiedOrder::factory()->count(3)->create(['channel_id' => $channel2->id]);

    $orders = UnifiedOrder::byChannel($channel1->id)->get();

    expect($orders)->toHaveCount(5)
        ->and($orders->pluck('channel_id')->unique()->toArray())->toBe([$channel1->id]);
});

it('scopes orders by status', function () {
    UnifiedOrder::factory()->count(3)->create(['status' => 'completed']);
    UnifiedOrder::factory()->count(2)->create(['status' => 'pending']);
    UnifiedOrder::factory()->count(4)->create(['status' => 'processing']);

    $completed = UnifiedOrder::byStatus('completed')->get();
    $pending = UnifiedOrder::byStatus('pending')->get();

    expect($completed)->toHaveCount(3)
        ->and($pending)->toHaveCount(2);
});

it('scopes orders by date range', function () {
    UnifiedOrder::factory()->create(['order_date' => now()->subDays(10)]);
    UnifiedOrder::factory()->create(['order_date' => now()->subDays(5)]);
    UnifiedOrder::factory()->create(['order_date' => now()->subDay()]);

    $orders = UnifiedOrder::byDateRange(now()->subDays(7), now())->get();

    expect($orders)->toHaveCount(2);
});

it('scopes orders by customer', function () {
    UnifiedOrder::factory()->count(3)->create(['customer_email' => 'customer1@example.com']);
    UnifiedOrder::factory()->count(2)->create(['customer_email' => 'customer2@example.com']);

    $orders = UnifiedOrder::byCustomer('customer1@example.com')->get();

    expect($orders)->toHaveCount(3);
});

it('has fillable attributes', function () {
    $order = new UnifiedOrder();

    expect($order->getFillable())->toBeArray()
        ->and($order->getFillable())->toContain('order_number', 'channel_id', 'status', 'total_amount');
});

it('casts attributes correctly', function () {
    $order = UnifiedOrder::factory()->create([
        'order_date' => '2024-01-15 10:00:00',
        'total_amount' => '1234.56',
        'additional_data' => ['key' => 'value'],
    ]);

    expect($order->order_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($order->total_amount)->toBeFloat()
        ->and($order->additional_data)->toBeArray();
});

it('generates unique order numbers', function () {
    $order1 = UnifiedOrder::factory()->create();
    $order2 = UnifiedOrder::factory()->create();

    expect($order1->order_number)->not->toBe($order2->order_number);
});

it('soft deletes orders', function () {
    $order = UnifiedOrder::factory()->create();
    $orderId = $order->id;

    $order->delete();

    expect(UnifiedOrder::find($orderId))->toBeNull()
        ->and(UnifiedOrder::withTrashed()->find($orderId))->not->toBeNull();
});

it('restores soft deleted orders', function () {
    $order = UnifiedOrder::factory()->create();
    $orderId = $order->id;

    $order->delete();
    $order->restore();

    expect(UnifiedOrder::find($orderId))->not->toBeNull();
});

it('tracks tenant isolation', function () {
    $order = UnifiedOrder::factory()->create();

    expect($order->tenant_id)->not->toBeNull()
        ->and($order->tenant)->not->toBeNull();
});
