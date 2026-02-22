<?php

use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
    $this->token = $this->admin->createToken('test-token')->accessToken;
});

it('can get order profitability via API', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/profitability/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'total_revenue',
                'total_cost',
                'total_profit',
                'margin_percentage',
            ],
        ])
        ->assertJsonPath('data.total_profit', 400.00)
        ->assertJsonPath('data.margin_percentage', 40.00);
});

it('can get channel profitability via API', function () {
    $channel = $this->createTestChannel();

    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['channel_id' => $channel->id]);

    $order2 = createOrderWithProfitability(revenue: 500.00, cost: 300.00);
    $order2->update(['channel_id' => $channel->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/profitability/channels/{$channel->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.total_revenue', 1500.00)
        ->assertJsonPath('data.total_profit', 600.00);
});

it('can get profitability by date range via API', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order->update(['order_date' => now()->subDays(3)]);

    $fromDate = now()->subDays(7)->format('Y-m-d');
    $toDate = now()->format('Y-m-d');

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/profitability?date_from={$fromDate}&date_to={$toDate}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'total_revenue',
                'total_cost',
                'total_profit',
                'margin_percentage',
            ],
        ]);
});

it('can compare channel profitability via API', function () {
    $channel1 = $this->createTestChannel(['code' => 'channel-1']);
    $channel2 = $this->createTestChannel(['code' => 'channel-2']);

    $order1 = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    $order1->update(['channel_id' => $channel1->id]);

    $order2 = createOrderWithProfitability(revenue: 2000.00, cost: 1000.00);
    $order2->update(['channel_id' => $channel2->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/profitability/compare?channels[]={$channel1->id}&channels[]={$channel2->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'channel_id',
                    'total_revenue',
                    'total_profit',
                    'margin_percentage',
                ],
            ],
        ])
        ->assertJsonCount(2, 'data');
});

it('can get item profitability breakdown via API', function () {
    $order = $this->createOrderWithItems(3);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/profitability/orders/{$order->id}/items");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'item_id',
                    'sku',
                    'name',
                    'profit',
                    'margin_percentage',
                ],
            ],
        ]);
});

it('can get profitability trends via API', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/profitability/trends?period=30');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'trends' => [
                    '*' => [
                        'date',
                        'revenue',
                        'profit',
                        'margin',
                    ],
                ],
            ],
        ]);
});

it('can export profitability report via API', function () {
    createOrderWithProfitability(revenue: 1000.00, cost: 600.00);
    createOrderWithProfitability(revenue: 500.00, cost: 300.00);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/profitability/export');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json');
});

it('requires authentication for profitability API', function () {
    $response = $this->getJson('/api/v1/order/profitability');

    $response->assertStatus(401);
});

it('validates date range parameters', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/profitability?date_from=invalid-date');

    $response->assertStatus(422);
});

it('returns 404 for non-existent order profitability', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/profitability/orders/99999');

    $response->assertStatus(404);
});

it('respects tenant isolation in profitability API', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $otherTenantAdmin = Admin::factory()->create(['tenant_id' => 999]);
    $otherToken = $otherTenantAdmin->createToken('test-token')->accessToken;

    $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->getJson("/api/v1/order/profitability/orders/{$order->id}");

    $response->assertStatus(404);
});
