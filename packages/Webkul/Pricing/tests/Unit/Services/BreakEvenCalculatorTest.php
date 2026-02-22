<?php

use Webkul\Pricing\Services\BreakEvenCalculator;
use Webkul\Pricing\ValueObjects\BreakEvenResult;
use Webkul\Product\Models\Product;
use Webkul\Core\Models\Channel;
use Webkul\Pricing\Models\ProductCost;
use Webkul\Pricing\Models\ChannelCost;

beforeEach(function () {
    $this->service = app(BreakEvenCalculator::class);
    $this->loginAsAdmin();
});

it('should calculate break-even price with only fixed costs', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    $result = $this->service->calculate($product->id);

    expect($result)
        ->toBeInstanceOf(BreakEvenResult::class)
        ->and($result->breakEvenPrice)->toBeGreaterThan(100.00)
        ->and($result->fixedCosts)->toBe(100.00)
        ->and($result->currency)->toBe('USD');
});

it('should calculate break-even price with channel costs', function () {
    $product = Product::factory()->create();
    $channel = Channel::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    ChannelCost::factory()->create([
        'channel_id' => $channel->id,
        'commission_percentage' => 10.00,
        'transaction_fee_percentage' => 2.50,
        'effective_from' => now()->subDay(),
    ]);

    $result = $this->service->calculate($product->id, $channel->id);

    expect($result)
        ->toBeInstanceOf(BreakEvenResult::class)
        ->and($result->variableRate)->toBeGreaterThan(0.10)
        ->and($result->breakEvenPrice)->toBeGreaterThan(100.00);
});

it('should return null when no costs exist', function () {
    $product = Product::factory()->create();

    $result = $this->service->calculate($product->id);

    expect($result)->toBeNull();
});

it('should handle impossible scenarios with variable rate >= 1', function () {
    $product = Product::factory()->create();
    $channel = Channel::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    ChannelCost::factory()->create([
        'channel_id' => $channel->id,
        'commission_percentage' => 95.00,
        'transaction_fee_percentage' => 10.00,
        'effective_from' => now()->subDay(),
    ]);

    $result = $this->service->calculate($product->id, $channel->id);

    expect($result)->toBeNull();
});

it('should aggregate multiple cost types correctly', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'shipping',
        'amount' => 20.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'overhead',
        'amount' => 15.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    $result = $this->service->calculate($product->id);

    expect($result->fixedCosts)->toBe(135.00);
});

it('should only use active costs within date range', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
        'effective_to' => now()->addMonth(),
    ]);

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 200.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subYear(),
        'effective_to' => now()->subMonth(),
    ]);

    $result = $this->service->calculate($product->id);

    expect($result->fixedCosts)->toBe(100.00);
});
