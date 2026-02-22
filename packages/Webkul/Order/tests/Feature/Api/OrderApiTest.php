<?php

use Webkul\Order\Models\UnifiedOrder;
use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
    $this->token = $this->admin->createToken('test-token')->accessToken;
});

it('can list orders via API', function () {
    UnifiedOrder::factory()->count(10)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/orders');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'order_number', 'status', 'total_amount'],
            ],
            'meta' => ['total', 'per_page', 'current_page'],
        ]);
});

it('can get single order via API', function () {
    $order = UnifiedOrder::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);
});

it('can filter orders by status via API', function () {
    UnifiedOrder::factory()->count(5)->create(['status' => 'completed']);
    UnifiedOrder::factory()->count(3)->create(['status' => 'pending']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/orders?status=completed');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

it('can filter orders by channel via API', function () {
    $channel = $this->createTestChannel();

    UnifiedOrder::factory()->count(4)->create(['channel_id' => $channel->id]);
    UnifiedOrder::factory()->count(3)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/orders?channel_id={$channel->id}");

    $response->assertStatus(200)
        ->assertJsonCount(4, 'data');
});

it('can search orders via API', function () {
    UnifiedOrder::factory()->create(['order_number' => 'SEARCH-123']);
    UnifiedOrder::factory()->create(['order_number' => 'OTHER-456']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/orders?search=SEARCH-123');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.order_number', 'SEARCH-123');
});

it('can paginate orders via API', function () {
    UnifiedOrder::factory()->count(25)->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/orders?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.per_page', 10);
});

it('can update order status via API', function () {
    $order = UnifiedOrder::factory()->create(['status' => 'pending']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson("/api/v1/order/orders/{$order->id}", [
            'status' => 'processing',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'processing');

    expect($order->fresh()->status)->toBe('processing');
});

it('requires authentication for API access', function () {
    $response = $this->getJson('/api/v1/order/orders');

    $response->assertStatus(401);
});

it('validates API request parameters', function () {
    $order = UnifiedOrder::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson("/api/v1/order/orders/{$order->id}", [
            'status' => 'invalid-status',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('returns 404 for non-existent order', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/order/orders/99999');

    $response->assertStatus(404);
});

it('includes order items in API response', function () {
    $order = $this->createOrderWithItems(3);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/orders/{$order->id}?include=items");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'order_items' => [
                    '*' => ['id', 'sku', 'name', 'price', 'quantity'],
                ],
            ],
        ]);
});

it('includes profitability in API response', function () {
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/order/orders/{$order->id}?include=profitability");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'profitability' => [
                    'total_profit',
                    'margin_percentage',
                    'total_revenue',
                    'total_cost',
                ],
            ],
        ]);
});

it('respects tenant isolation in API', function () {
    $order = UnifiedOrder::factory()->create();

    // Create different tenant admin
    $otherTenantAdmin = Admin::factory()->create(['tenant_id' => 999]);
    $otherToken = $otherTenantAdmin->createToken('test-token')->accessToken;

    $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->getJson("/api/v1/order/orders/{$order->id}");

    $response->assertStatus(404);
});
