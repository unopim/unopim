<?php

use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Services\ProfitabilityCalculator;

beforeEach(function () {
    $this->admin = $this->createAdminWithOrderPermissions([
        'order.profitability.view',
        'order.profitability.view-costs',
        'order.profitability.view-margins',
    ]);
    $this->actingAs($this->admin, 'admin');
});

it('can view profitability dashboard', function () {
    $response = $this->get(route('admin.order.profitability.index'));

    $response->assertStatus(200)
        ->assertViewIs('order::profitability.index');
});

it('displays overall profitability metrics', function () {
    createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    createOrderWithProfitability(revenue: 500.00, cost: 300.00);

    $response = $this->get(route('admin.order.profitability.index'));

    $response->assertStatus(200)
        ->assertViewHas('profitability')
        ->assertSee('1500.00') // Total revenue
        ->assertSee('600.00'); // Total profit
});

it('can view single order profitability', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertViewIs('order::profitability.show')
        ->assertViewHas('order')
        ->assertSee('400.00'); // Profit
});

it('can filter profitability by date range', function () {
    $response = $this->get(route('admin.order.profitability.index', [
        'date_from' => now()->subDays(30)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]));

    $response->assertStatus(200);
});

it('can compare channel profitability', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.profitability.channel-comparison']);
    $this->actingAs($this->admin, 'admin');

    $channel1 = $this->createTestChannel(['code' => 'channel-1']);
    $channel2 = $this->createTestChannel(['code' => 'channel-2']);

    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['channel_id' => $channel1->id]);

    $order2 = createOrderWithProfitability(revenue: 2000.00, cost: 1000.00);
    $order2->update(['channel_id' => $channel2->id]);

    $response = $this->get(route('admin.order.profitability.channel-comparison'));

    $response->assertStatus(200)
        ->assertViewIs('order::profitability.channel-comparison');
});

it('hides costs without proper permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.view']);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertDontSee('600.00'); // Cost should be hidden
});

it('shows costs with proper permission', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertSee('600.00'); // Cost should be visible
});

it('hides margins without proper permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.view']);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertDontSee('40.00%'); // Margin should be hidden
});

it('can export profitability report', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.profitability.export']);
    $this->actingAs($this->admin, 'admin');

    createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    createOrderWithProfitability(revenue: 500.00, cost: 300.00);

    $response = $this->post(route('admin.order.profitability.export'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/csv');
});

it('displays profitability trends over time', function () {
    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['order_date' => now()->subDays(30)]);

    $order2 = createOrderWithProfitability(revenue: 1500.00, cost: 900.00);
    $order2->update(['order_date' => now()]);

    $response = $this->get(route('admin.order.profitability.trends'));

    $response->assertStatus(200)
        ->assertViewHas('trends');
});

it('displays top profitable products', function () {
    $response = $this->get(route('admin.order.profitability.top-products'));

    $response->assertStatus(200)
        ->assertViewIs('order::profitability.top-products');
});

it('displays profitability by customer', function () {
    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['customer_email' => 'customer1@example.com']);

    $order2 = createOrderWithProfitability(revenue: 2000.00, cost: 1000.00);
    $order2->update(['customer_email' => 'customer1@example.com']);

    $response = $this->get(route('admin.order.profitability.by-customer'));

    $response->assertStatus(200)
        ->assertSee('customer1@example.com')
        ->assertSee('1400.00'); // Total profit for this customer
});

it('can configure profitability settings', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.profitability.settings']);
    $this->actingAs($this->admin, 'admin');

    $response = $this->get(route('admin.order.profitability.settings'));

    $response->assertStatus(200)
        ->assertViewIs('order::profitability.settings');
});

it('calculates average order value', function () {
    createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    createOrderWithProfitability(revenue: 500.00, cost: 300.00);

    $response = $this->get(route('admin.order.profitability.index'));

    $response->assertStatus(200)
        ->assertSee('750.00'); // Average order value
});
