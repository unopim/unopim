<?php

use Webkul\Order\ValueObjects\ProfitabilityResult;

it('can create profitability result', function () {
    $result = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 600.00,
        totalProfit: 400.00,
        marginPercentage: 40.00
    );

    expect($result->totalRevenue)->toBe(1000.00)
        ->and($result->totalCost)->toBe(600.00)
        ->and($result->totalProfit)->toBe(400.00)
        ->and($result->marginPercentage)->toBe(40.00);
});

it('calculates profit correctly', function () {
    $result = ProfitabilityResult::calculate(
        revenue: 1000.00,
        cost: 600.00
    );

    expect($result->totalProfit)->toBe(400.00)
        ->and($result->marginPercentage)->toBe(40.00);
});

it('handles zero revenue correctly', function () {
    $result = ProfitabilityResult::calculate(
        revenue: 0.00,
        cost: 0.00
    );

    expect($result->totalProfit)->toBe(0.00)
        ->and($result->marginPercentage)->toBe(0.00);
});

it('calculates negative profit for loss', function () {
    $result = ProfitabilityResult::calculate(
        revenue: 100.00,
        cost: 150.00
    );

    expect($result->totalProfit)->toBe(-50.00)
        ->and($result->marginPercentage)->toBeLessThan(0.00);
});

it('formats margin as percentage', function () {
    $result = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 600.00,
        totalProfit: 400.00,
        marginPercentage: 40.00
    );

    expect($result->getFormattedMargin())->toBe('40.00%');
});

it('converts to array correctly', function () {
    $result = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 600.00,
        totalProfit: 400.00,
        marginPercentage: 40.00
    );

    $array = $result->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys(['total_revenue', 'total_cost', 'total_profit', 'margin_percentage'])
        ->and($array['total_profit'])->toBe(400.00);
});

it('determines if profitable', function () {
    $profitable = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 600.00,
        totalProfit: 400.00,
        marginPercentage: 40.00
    );

    $loss = new ProfitabilityResult(
        totalRevenue: 100.00,
        totalCost: 150.00,
        totalProfit: -50.00,
        marginPercentage: -50.00
    );

    expect($profitable->isProfitable())->toBeTrue()
        ->and($loss->isProfitable())->toBeFalse();
});

it('determines margin health level', function () {
    $high = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 400.00,
        totalProfit: 600.00,
        marginPercentage: 60.00
    );

    $medium = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 700.00,
        totalProfit: 300.00,
        marginPercentage: 30.00
    );

    $low = new ProfitabilityResult(
        totalRevenue: 1000.00,
        totalCost: 950.00,
        totalProfit: 50.00,
        marginPercentage: 5.00
    );

    expect($high->getMarginHealth())->toBe('excellent')
        ->and($medium->getMarginHealth())->toBe('good')
        ->and($low->getMarginHealth())->toBe('poor');
});
