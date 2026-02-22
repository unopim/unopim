<?php

use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;
use Webkul\Order\Services\ProfitabilityCalculator;

beforeEach(function () {
    $this->calculator = app(ProfitabilityCalculator::class);
});

it('calculates single order profitability', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $result = $this->calculator->calculateOrderProfitability($order->id);

    expect($result)->toHaveProfitability()
        ->and($result['total_revenue'])->toBe(1000.00)
        ->and($result['total_cost'])->toBe(600.00)
        ->and($result['total_profit'])->toBe(400.00)
        ->and($result['margin_percentage'])->toBe(40.00);
});

it('calculates channel profitability', function () {
    $channel = $this->createTestChannel();

    // Create 3 orders for this channel
    createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    createOrderWithProfitability(revenue: 500.00, cost: 300.00);
    createOrderWithProfitability(revenue: 800.00, cost: 500.00);

    UnifiedOrder::query()->update(['channel_id' => $channel->id]);

    $result = $this->calculator->calculateChannelProfitability($channel->id);

    expect($result)->toHaveProfitability()
        ->and($result['total_revenue'])->toBe(2300.00)
        ->and($result['total_cost'])->toBe(1400.00)
        ->and($result['total_profit'])->toBe(900.00);
});

it('calculates profitability by date range', function () {
    $fromDate = now()->subDays(7);
    $toDate = now();

    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['order_date' => now()->subDays(3)]);

    $order2 = createOrderWithProfitability(revenue: 500.00, cost: 300.00);
    $order2->update(['order_date' => now()->subDays(10)]);

    $result = $this->calculator->calculateProfitabilityByDateRange($fromDate, $toDate);

    expect($result)->toHaveProfitability()
        ->and($result['total_revenue'])->toBe(1000.00) // Only order1
        ->and($result['total_profit'])->toBe(400.00);
});

it('calculates item profitability breakdown', function () {
    $order = UnifiedOrder::factory()->create(['total_amount' => 1500.00]);

    UnifiedOrderItem::factory()->create([
        'unified_order_id' => $order->id,
        'price' => 500.00,
        'quantity' => 2,
        'cost_basis' => 300.00,
    ]);

    UnifiedOrderItem::factory()->create([
        'unified_order_id' => $order->id,
        'price' => 250.00,
        'quantity' => 2,
        'cost_basis' => 150.00,
    ]);

    $result = $this->calculator->calculateItemProfitabilityBreakdown($order->id);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toHaveKeys(['item_id', 'profit', 'margin_percentage'])
        ->and($result[0]['profit'])->toBe(400.00) // (500 - 300) * 2
        ->and($result[1]['profit'])->toBe(200.00); // (250 - 150) * 2
});

it('compares channel profitability', function () {
    $channel1 = $this->createTestChannel(['code' => 'channel-1']);
    $channel2 = $this->createTestChannel(['code' => 'channel-2']);

    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['channel_id' => $channel1->id]);

    $order2 = createOrderWithProfitability(revenue: 2000.00, cost: 1000.00);
    $order2->update(['channel_id' => $channel2->id]);

    $result = $this->calculator->compareChannelProfitability([$channel1->id, $channel2->id]);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0]['channel_id'])->toBe($channel1->id)
        ->and($result[0]['total_profit'])->toBe(400.00)
        ->and($result[1]['channel_id'])->toBe($channel2->id)
        ->and($result[1]['total_profit'])->toBe(1000.00);
});

it('handles orders without cost basis', function () {
    $order = UnifiedOrder::factory()->create(['total_amount' => 1000.00]);

    UnifiedOrderItem::factory()->create([
        'unified_order_id' => $order->id,
        'price' => 1000.00,
        'quantity' => 1,
        'cost_basis' => null,
    ]);

    $result = $this->calculator->calculateOrderProfitability($order->id);

    expect($result)->toHaveProfitability()
        ->and($result['total_cost'])->toBe(0.00)
        ->and($result['total_profit'])->toBe(1000.00);
});

it('calculates average margin across orders', function () {
    createOrderWithProfitability(revenue: 1000.00, cost: 600.00); // 40% margin
    createOrderWithProfitability(revenue: 1000.00, cost: 800.00); // 20% margin

    $result = $this->calculator->calculateAverageMargin();

    expect($result)->toBeFloat()
        ->and($result)->toBe(30.00); // Average of 40 and 20
});

it('exports profitability data for reporting', function () {
    $channel = $this->createTestChannel();

    createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    createOrderWithProfitability(revenue: 500.00, cost: 300.00);

    UnifiedOrder::query()->update(['channel_id' => $channel->id]);

    $export = $this->calculator->exportProfitabilityReport($channel->id);

    expect($export)->toBeArray()
        ->and($export)->toHaveKeys(['summary', 'details', 'generated_at'])
        ->and($export['summary'])->toHaveProfitability();
});
