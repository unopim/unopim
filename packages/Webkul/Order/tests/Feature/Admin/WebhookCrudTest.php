<?php

use Webkul\Order\Models\OrderWebhook;

beforeEach(function () {
    $this->admin = $this->createAdminWithOrderPermissions([
        'order.webhooks.view',
        'order.webhooks.create',
        'order.webhooks.edit',
        'order.webhooks.delete',
    ]);
    $this->actingAs($this->admin, 'admin');
    $this->channel = $this->createTestChannel();
});

it('can view webhooks index page', function () {
    $response = $this->get(route('admin.order.webhooks.index'));

    $response->assertStatus(200)
        ->assertViewIs('order::webhooks.index');
});

it('can view create webhook form', function () {
    $response = $this->get(route('admin.order.webhooks.create'));

    $response->assertStatus(200)
        ->assertViewIs('order::webhooks.create');
});

it('can create new webhook', function () {
    $response = $this->post(route('admin.order.webhooks.store'), [
        'channel_id' => $this->channel->id,
        'event_types' => ['order.created', 'order.updated'],
        'is_active' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(OrderWebhook::count())->toBe(1);

    $webhook = OrderWebhook::first();
    expect($webhook->channel_id)->toBe($this->channel->id)
        ->and($webhook->event_types)->toContain('order.created')
        ->and($webhook->is_active)->toBeTrue();
});

it('auto-generates secret key when creating webhook', function () {
    $response = $this->post(route('admin.order.webhooks.store'), [
        'channel_id' => $this->channel->id,
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $webhook = OrderWebhook::first();
    expect($webhook->secret_key)->not->toBeNull()
        ->and(strlen($webhook->secret_key))->toBeGreaterThan(20);
});

it('can view edit webhook form', function () {
    $webhook = OrderWebhook::factory()->create();

    $response = $this->get(route('admin.order.webhooks.edit', $webhook->id));

    $response->assertStatus(200)
        ->assertViewIs('order::webhooks.edit')
        ->assertViewHas('webhook');
});

it('can update webhook', function () {
    $webhook = OrderWebhook::factory()->create([
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $response = $this->put(route('admin.order.webhooks.update', $webhook->id), [
        'event_types' => ['order.created', 'order.updated', 'order.cancelled'],
        'is_active' => false,
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect($webhook->fresh()->event_types)->toHaveCount(3)
        ->and($webhook->fresh()->is_active)->toBeFalse();
});

it('can delete webhook', function () {
    $webhook = OrderWebhook::factory()->create();

    $response = $this->delete(route('admin.order.webhooks.destroy', $webhook->id));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(OrderWebhook::find($webhook->id))->toBeNull();
});

it('can activate webhook', function () {
    $webhook = OrderWebhook::factory()->create(['is_active' => false]);

    $response = $this->post(route('admin.order.webhooks.activate', $webhook->id));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect($webhook->fresh()->is_active)->toBeTrue();
});

it('can deactivate webhook', function () {
    $webhook = OrderWebhook::factory()->create(['is_active' => true]);

    $response = $this->post(route('admin.order.webhooks.deactivate', $webhook->id));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect($webhook->fresh()->is_active)->toBeFalse();
});

it('can regenerate webhook secret key', function () {
    $webhook = OrderWebhook::factory()->create(['secret_key' => 'old-secret']);

    $response = $this->post(route('admin.order.webhooks.regenerate-secret', $webhook->id));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect($webhook->fresh()->secret_key)->not->toBe('old-secret');
});

it('can test webhook delivery', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.webhooks.test']);
    $this->actingAs($this->admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->post(route('admin.order.webhooks.test', $webhook->id));

    $response->assertRedirect()
        ->assertSessionHas('success');
});

it('can view webhook delivery logs', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.webhooks.logs']);
    $this->actingAs($this->admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->get(route('admin.order.webhooks.logs', $webhook->id));

    $response->assertStatus(200)
        ->assertViewIs('order::webhooks.logs')
        ->assertViewHas('webhook');
});

it('validates required fields when creating', function () {
    $response = $this->post(route('admin.order.webhooks.store'), [
        'channel_id' => null,
        'event_types' => [],
    ]);

    $response->assertSessionHasErrors(['channel_id', 'event_types']);
});

it('validates channel exists', function () {
    $response = $this->post(route('admin.order.webhooks.store'), [
        'channel_id' => 99999,
        'event_types' => ['order.created'],
    ]);

    $response->assertSessionHasErrors('channel_id');
});

it('validates event types are valid', function () {
    $response = $this->post(route('admin.order.webhooks.store'), [
        'channel_id' => $this->channel->id,
        'event_types' => ['invalid.event'],
    ]);

    $response->assertSessionHasErrors('event_types');
});

it('displays webhook statistics', function () {
    $webhook = OrderWebhook::factory()->create([
        'delivery_attempts' => 10,
        'last_delivery_at' => now()->subHours(2),
    ]);

    $response = $this->get(route('admin.order.webhooks.index'));

    $response->assertStatus(200)
        ->assertSee('10'); // Delivery attempts
});
