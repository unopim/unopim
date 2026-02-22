<?php

use Webkul\Order\Models\OrderWebhook;
use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Services\WebhookProcessor;

beforeEach(function () {
    $this->processor = app(WebhookProcessor::class);
});

it('processes order created event', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
        'channel_code' => 'salla',
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

    $result = $this->processor->processWebhook($webhook->id, $payload);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('success')
        ->and(UnifiedOrder::where('external_id', '123')->exists())->toBeTrue();
});

it('processes order updated event', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.updated'],
        'channel_code' => 'salla',
    ]);

    $order = UnifiedOrder::factory()->create([
        'external_id' => '123',
        'status' => 'pending',
    ]);

    $payload = [
        'event' => 'order.updated',
        'data' => [
            'id' => '123',
            'status' => 'completed',
        ],
    ];

    $result = $this->processor->processWebhook($webhook->id, $payload);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('success')
        ->and($order->fresh()->status)->toBe('completed');
});

it('processes order cancelled event', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.cancelled'],
        'channel_code' => 'salla',
    ]);

    $order = UnifiedOrder::factory()->create([
        'external_id' => '123',
        'status' => 'pending',
    ]);

    $payload = [
        'event' => 'order.cancelled',
        'data' => [
            'id' => '123',
        ],
    ];

    $result = $this->processor->processWebhook($webhook->id, $payload);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('success')
        ->and($order->fresh()->status)->toBe('cancelled');
});

it('validates webhook signature before processing', function () {
    $webhook = OrderWebhook::factory()->create([
        'secret_key' => 'test-secret',
        'event_types' => ['order.created'],
    ]);

    $payload = ['event' => 'order.created', 'data' => ['id' => '123']];
    $invalidSignature = 'invalid-signature';

    $result = $this->processor->processWebhook($webhook->id, $payload, $invalidSignature);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('error')
        ->and($result['error'])->toContain('Invalid signature');
});

it('handles unknown event types gracefully', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
    ]);

    $payload = [
        'event' => 'order.unknown_event',
        'data' => [],
    ];

    $result = $this->processor->processWebhook($webhook->id, $payload);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('error')
        ->and($result['error'])->toContain('Unsupported event type');
});

it('increments delivery attempts on failure', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
        'delivery_attempts' => 0,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => [], // Missing required fields
    ];

    $this->processor->processWebhook($webhook->id, $payload);

    expect($webhook->fresh()->delivery_attempts)->toBeGreaterThan(0);
});

it('records last delivery timestamp', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
        'last_delivery_at' => null,
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

    $this->processor->processWebhook($webhook->id, $payload);

    expect($webhook->fresh()->last_delivery_at)->not->toBeNull();
});

it('deactivates webhook after max failed attempts', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
        'delivery_attempts' => 4,
        'is_active' => true,
    ]);

    $payload = [
        'event' => 'order.created',
        'data' => [], // Will fail
    ];

    $this->processor->processWebhook($webhook->id, $payload);

    expect($webhook->fresh()->is_active)->toBeFalse();
});

it('processes batch webhook events', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
    ]);

    $payloads = [
        ['event' => 'order.created', 'data' => ['id' => '1', 'order_number' => 'ORD-001', 'total_amount' => 100]],
        ['event' => 'order.created', 'data' => ['id' => '2', 'order_number' => 'ORD-002', 'total_amount' => 200]],
        ['event' => 'order.created', 'data' => ['id' => '3', 'order_number' => 'ORD-003', 'total_amount' => 300]],
    ];

    $result = $this->processor->processBatchWebhooks($webhook->id, $payloads);

    expect($result)->toBeArray()
        ->and($result['processed_count'])->toBe(3)
        ->and($result['success_count'])->toBe(3);
});

it('validates webhook is active before processing', function () {
    $webhook = OrderWebhook::factory()->create([
        'is_active' => false,
    ]);

    $payload = ['event' => 'order.created', 'data' => []];

    $result = $this->processor->processWebhook($webhook->id, $payload);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('error')
        ->and($result['error'])->toContain('Webhook is not active');
});
