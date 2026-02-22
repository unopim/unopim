<?php

use Webkul\Pricing\Services\MarginProtector;
use Webkul\Pricing\ValueObjects\MarginValidationResult;
use Webkul\Product\Models\Product;
use Webkul\Core\Models\Channel;
use Webkul\Pricing\Models\ProductCost;
use Webkul\Pricing\Models\PricingStrategy;
use Webkul\Pricing\Models\MarginProtectionEvent;

beforeEach(function () {
    $this->service = app(MarginProtector::class);
    $this->loginAsAdmin();
});

it('should block price below break-even', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    $result = $this->service->validate(95.00, $product->id);

    expect($result)
        ->toBeInstanceOf(MarginValidationResult::class)
        ->and($result->isBlocked())->toBeTrue()
        ->and($result->status)->toBe('blocked');

    $this->assertDatabaseHas('margin_protection_events', [
        'product_id' => $product->id,
        'event_type' => 'blocked',
    ]);
});

it('should allow price above minimum margin', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    PricingStrategy::factory()->create([
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 20.00,
        'target_margin_percentage' => 30.00,
        'premium_margin_percentage' => 40.00,
        'is_active' => true,
    ]);

    $result = $this->service->validate(150.00, $product->id);

    expect($result->isOk())->toBeTrue()
        ->and($result->status)->toBe('ok');
});

it('should warn when price is below target margin', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    PricingStrategy::factory()->create([
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 20.00,
        'target_margin_percentage' => 40.00,
        'premium_margin_percentage' => 50.00,
        'is_active' => true,
    ]);

    $result = $this->service->validate(125.00, $product->id);

    expect($result->isWarning())->toBeTrue()
        ->and($result->status)->toBe('warning');
});

it('should auto-approve when enabled', function () {
    $product = Product::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    $result = $this->service->validate(95.00, $product->id, autoApprove: true);

    expect($result->isOk())->toBeTrue();

    $this->assertDatabaseHas('margin_protection_events', [
        'product_id' => $product->id,
        'event_type' => 'approved',
    ]);
});

it('should respect channel-specific strategies over global', function () {
    $product = Product::factory()->create();
    $channel = Channel::factory()->create();

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 100.00,
        'currency_code' => 'USD',
        'effective_from' => now()->subDay(),
    ]);

    PricingStrategy::factory()->create([
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 20.00,
        'target_margin_percentage' => 30.00,
        'premium_margin_percentage' => 40.00,
        'is_active' => true,
        'priority' => 100,
    ]);

    PricingStrategy::factory()->create([
        'scope_type' => 'channel',
        'scope_id' => $channel->id,
        'minimum_margin_percentage' => 10.00,
        'target_margin_percentage' => 20.00,
        'premium_margin_percentage' => 30.00,
        'is_active' => true,
        'priority' => 50,
    ]);

    $result = $this->service->validate(115.00, $product->id, $channel->id);

    expect($result->isOk())->toBeTrue();
});
