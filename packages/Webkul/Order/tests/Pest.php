<?php

use Webkul\Order\Tests\OrderTestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(OrderTestCase::class)->in('Unit', 'Feature', 'Architecture');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toHaveProfitability', function () {
    return $this->toBeArray()
        ->toHaveKeys(['total_profit', 'margin_percentage', 'total_revenue', 'total_cost']);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createOrderWithProfitability(float $revenue, float $cost): \Webkul\Order\Models\UnifiedOrder
{
    $order = \Webkul\Order\Models\UnifiedOrder::factory()->create([
        'total_amount' => $revenue,
    ]);

    \Webkul\Order\Models\UnifiedOrderItem::factory()->create([
        'unified_order_id' => $order->id,
        'price' => $revenue,
        'quantity' => 1,
        'cost_basis' => $cost,
    ]);

    return $order->fresh('orderItems');
}
