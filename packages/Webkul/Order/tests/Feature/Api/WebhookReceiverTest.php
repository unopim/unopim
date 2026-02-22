<?php

use Webkul\Order\Models\OrderWebhook;
use Webkul\Order\Models\UnifiedOrder;

it('receives and verifies HMAC signature', function () {
    $webhook = OrderWebhook::factory()->create([
        'secret_key' => 'test-secret-key',
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => [
            'id' => '123',
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 100.00,
            'customer_email' => 'test@example.com',
        ],
    ];

    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret-key');

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);
});

it('rejects invalid HMAC signature', function () {
    $webhook = OrderWebhook::factory()->create([
        'secret_key' => 'test-secret-key',
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => ['id' => '123'],
    ];

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => 'invalid-signature',
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid signature']);
});

it('processes order created event', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => [
            'id' => '123',
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 100.00,
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test Customer',
        ],
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(200);

    expect(UnifiedOrder::where('external_id', '123')->exists())->toBeTrue();

    $order = UnifiedOrder::where('external_id', '123')->first();
    expect($order->order_number)->toBe('ORD-001')
        ->and($order->status)->toBe('pending')
        ->and($order->total_amount)->toBe(100.00);
});

it('processes order updated event', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.updated'],
        'is_active' => true,
    ]);

    $order = UnifiedOrder::factory()->create([
        'external_id' => '123',
        'status' => 'pending',
        'total_amount' => 100.00,
    ]);

    $payload = [
        'event' => 'order.updated',
        'data' => [
            'id' => '123',
            'status' => 'completed',
            'total_amount' => 150.00,
        ],
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(200);

    expect($order->fresh()->status)->toBe('completed')
        ->and($order->fresh()->total_amount)->toBe(150.00);
});

it('processes order cancelled event', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.cancelled'],
        'is_active' => true,
    ]);

    $order = UnifiedOrder::factory()->create([
        'external_id' => '123',
        'status' => 'pending',
    ]);

    $payload = [
        'event' => 'order.cancelled',
        'data' => [
            'id' => '123',
            'cancellation_reason' => 'Customer request',
        ],
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(200);

    expect($order->fresh()->status)->toBe('cancelled');
});

it('rejects webhook for inactive webhook config', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => false,
    ]);

    $payload = ['event' => 'order.created', 'data' => []];
    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(403)
        ->assertJson(['error' => 'Webhook is not active']);
});

it('rejects unsupported event types', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $payload = [
        'event' => 'order.unsupported_event',
        'data' => [],
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(400)
        ->assertJson(['error' => 'Unsupported event type']);
});

it('handles webhook processing errors gracefully', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => [
            // Missing required fields
        ],
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(422);
});

it('increments delivery attempts on webhook receipt', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'salla',
        'event_types' => ['order.created'],
        'is_active' => true,
        'delivery_attempts' => 0,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => [
            'id' => '123',
            'order_number' => 'ORD-001',
            'status' => 'pending',
            'total_amount' => 100.00,
        ],
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $this->postJson('/api/v1/order/webhooks/receive/salla', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    expect($webhook->fresh()->delivery_attempts)->toBeGreaterThan(0);
});

it('supports Shopify webhook format', function () {
    $webhook = OrderWebhook::factory()->create([
        'channel_code' => 'shopify',
        'event_types' => ['orders/create'],
        'is_active' => true,
    ]);

    $payload = [
        'id' => 12345,
        'email' => 'customer@example.com',
        'total_price' => '100.00',
        'financial_status' => 'pending',
    ];

    $signature = $this->generateWebhookSignature($payload, $webhook->secret_key);

    $response = $this->postJson('/api/v1/order/webhooks/receive/shopify', $payload, [
        'X-Shopify-Hmac-SHA256' => $signature,
    ]);

    $response->assertStatus(200);
});

it('returns 404 for unknown channel code', function () {
    $response = $this->postJson('/api/v1/order/webhooks/receive/unknown-channel', []);

    $response->assertStatus(404);
});
