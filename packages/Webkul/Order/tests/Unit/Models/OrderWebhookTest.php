<?php

use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderWebhook;

it('can create webhook with factory', function () {
    $webhook = OrderWebhook::factory()->create();

    expect($webhook)->toBeInstanceOf(OrderWebhook::class)
        ->and($webhook->id)->not->toBeNull()
        ->and($webhook->tenant_id)->not->toBeNull();
});

it('belongs to channel', function () {
    $channel = Channel::factory()->create();
    $webhook = OrderWebhook::factory()->create(['channel_id' => $channel->id]);

    expect($webhook->channel)->toBeInstanceOf(Channel::class)
        ->and($webhook->channel->id)->toBe($channel->id);
});

it('verifies valid HMAC signature', function () {
    $webhook = OrderWebhook::factory()->create(['secret_key' => 'test-secret-key']);
    $payload = ['event' => 'order.created', 'data' => ['id' => '123']];
    $signature = hash_hmac('sha256', json_encode($payload), 'test-secret-key');

    expect($webhook->verifySignature($payload, $signature))->toBeTrue();
});

it('rejects invalid HMAC signature', function () {
    $webhook = OrderWebhook::factory()->create(['secret_key' => 'test-secret-key']);
    $payload = ['event' => 'order.created', 'data' => ['id' => '123']];
    $invalidSignature = 'invalid-signature';

    expect($webhook->verifySignature($payload, $invalidSignature))->toBeFalse();
});

it('scopes webhooks by channel', function () {
    $channel1 = Channel::factory()->create();
    $channel2 = Channel::factory()->create();

    OrderWebhook::factory()->count(3)->create(['channel_id' => $channel1->id]);
    OrderWebhook::factory()->count(2)->create(['channel_id' => $channel2->id]);

    $webhooks = OrderWebhook::byChannel($channel1->id)->get();

    expect($webhooks)->toHaveCount(3);
});

it('scopes active webhooks', function () {
    OrderWebhook::factory()->count(3)->create(['is_active' => true]);
    OrderWebhook::factory()->count(2)->create(['is_active' => false]);

    $active = OrderWebhook::active()->get();

    expect($active)->toHaveCount(3);
});

it('scopes webhooks by event type', function () {
    OrderWebhook::factory()->count(2)->create(['event_types' => ['order.created']]);
    OrderWebhook::factory()->count(3)->create(['event_types' => ['order.updated']]);
    OrderWebhook::factory()->count(1)->create(['event_types' => ['order.created', 'order.updated']]);

    $createdWebhooks = OrderWebhook::byEventType('order.created')->get();

    expect($createdWebhooks)->toHaveCount(3); // 2 + 1 that has both
});

it('increments delivery attempts', function () {
    $webhook = OrderWebhook::factory()->create(['delivery_attempts' => 0]);

    $webhook->incrementDeliveryAttempts();

    expect($webhook->delivery_attempts)->toBe(1);
});

it('marks webhook as active', function () {
    $webhook = OrderWebhook::factory()->create(['is_active' => false]);

    $webhook->activate();

    expect($webhook->is_active)->toBeTrue();
});

it('marks webhook as inactive', function () {
    $webhook = OrderWebhook::factory()->create(['is_active' => true]);

    $webhook->deactivate();

    expect($webhook->is_active)->toBeFalse();
});

it('records last delivery', function () {
    $webhook = OrderWebhook::factory()->create(['last_delivery_at' => null]);

    $webhook->recordDelivery();

    expect($webhook->last_delivery_at)->not->toBeNull()
        ->and($webhook->last_delivery_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('has fillable attributes', function () {
    $webhook = new OrderWebhook();

    expect($webhook->getFillable())->toBeArray()
        ->and($webhook->getFillable())->toContain('channel_id', 'event_types', 'secret_key', 'is_active');
});

it('casts attributes correctly', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created', 'order.updated'],
        'is_active' => 1,
        'delivery_attempts' => '5',
        'last_delivery_at' => '2024-01-15 10:00:00',
    ]);

    expect($webhook->event_types)->toBeArray()
        ->and($webhook->is_active)->toBeBool()
        ->and($webhook->delivery_attempts)->toBeInt()
        ->and($webhook->last_delivery_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('generates secret key on creation', function () {
    $webhook = OrderWebhook::factory()->create(['secret_key' => null]);

    expect($webhook->secret_key)->not->toBeNull()
        ->and(strlen($webhook->secret_key))->toBeGreaterThan(20);
});

it('soft deletes webhooks', function () {
    $webhook = OrderWebhook::factory()->create();
    $webhookId = $webhook->id;

    $webhook->delete();

    expect(OrderWebhook::find($webhookId))->toBeNull()
        ->and(OrderWebhook::withTrashed()->find($webhookId))->not->toBeNull();
});
