<?php

use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;

beforeEach(function () {
    $this->admin = $this->createAdminWithOrderPermissions();
    $this->actingAs($this->admin, 'admin');
});

it('can view orders index page', function () {
    $response = $this->get(route('admin.order.orders.index'));

    $response->assertStatus(200)
        ->assertViewIs('order::orders.index');
});

it('displays paginated orders list', function () {
    UnifiedOrder::factory()->count(25)->create();

    $response = $this->get(route('admin.order.orders.index'));

    $response->assertStatus(200)
        ->assertViewHas('orders');
});

it('can view single order details', function () {
    $order = $this->createOrderWithItems(3);

    $response = $this->get(route('admin.order.orders.show', $order->id));

    $response->assertStatus(200)
        ->assertViewIs('order::orders.show')
        ->assertViewHas('order')
        ->assertSee($order->order_number);
});

it('can update order status', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.edit']);
    $this->actingAs($this->admin, 'admin');

    $order = UnifiedOrder::factory()->create(['status' => 'pending']);

    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'status' => 'processing',
        'internal_notes' => 'Updated to processing',
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('processing')
        ->and($order->fresh()->internal_notes)->toContain('Updated to processing');
});

it('can update order internal notes', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.edit']);
    $this->actingAs($this->admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'internal_notes' => 'Important customer',
    ]);

    $response->assertRedirect();

    expect($order->fresh()->internal_notes)->toBe('Important customer');
});

it('can delete order', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.delete']);
    $this->actingAs($this->admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    $response = $this->delete(route('admin.order.orders.destroy', $order->id));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(UnifiedOrder::find($order->id))->toBeNull();
});

it('can mass update order statuses', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.mass-update']);
    $this->actingAs($this->admin, 'admin');

    $orders = UnifiedOrder::factory()->count(3)->create(['status' => 'pending']);

    $response = $this->post(route('admin.order.orders.mass-update'), [
        'indices' => $orders->pluck('id')->toArray(),
        'status' => 'processing',
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $orders->each(function ($order) {
        expect($order->fresh()->status)->toBe('processing');
    });
});

it('can mass delete orders', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.mass-delete']);
    $this->actingAs($this->admin, 'admin');

    $orders = UnifiedOrder::factory()->count(3)->create();

    $response = $this->post(route('admin.order.orders.mass-destroy'), [
        'indices' => $orders->pluck('id')->toArray(),
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $orders->each(function ($order) {
        expect(UnifiedOrder::find($order->id))->toBeNull();
    });
});

it('can filter orders by status', function () {
    UnifiedOrder::factory()->count(3)->create(['status' => 'completed']);
    UnifiedOrder::factory()->count(2)->create(['status' => 'pending']);

    $response = $this->get(route('admin.order.orders.index', ['status' => 'completed']));

    $response->assertStatus(200);
    // Additional assertions on filtered data
});

it('can filter orders by channel', function () {
    $channel = $this->createTestChannel();

    UnifiedOrder::factory()->count(3)->create(['channel_id' => $channel->id]);
    UnifiedOrder::factory()->count(2)->create();

    $response = $this->get(route('admin.order.orders.index', ['channel_id' => $channel->id]));

    $response->assertStatus(200);
});

it('can filter orders by date range', function () {
    $response = $this->get(route('admin.order.orders.index', [
        'date_from' => now()->subDays(7)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]));

    $response->assertStatus(200);
});

it('can search orders by order number', function () {
    $order = UnifiedOrder::factory()->create(['order_number' => 'ORD-UNIQUE-123']);

    $response = $this->get(route('admin.order.orders.index', ['search' => 'UNIQUE-123']));

    $response->assertStatus(200)
        ->assertSee('ORD-UNIQUE-123');
});

it('can search orders by customer email', function () {
    $order = UnifiedOrder::factory()->create(['customer_email' => 'unique@example.com']);

    $response = $this->get(route('admin.order.orders.index', ['search' => 'unique@example.com']));

    $response->assertStatus(200)
        ->assertSee('unique@example.com');
});

it('can export orders', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.export']);
    $this->actingAs($this->admin, 'admin');

    UnifiedOrder::factory()->count(10)->create();

    $response = $this->post(route('admin.order.orders.export'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/csv');
});

it('validates required fields when updating', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.edit']);
    $this->actingAs($this->admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'status' => '', // Invalid empty status
    ]);

    $response->assertSessionHasErrors('status');
});

it('prevents unauthorized status transitions', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.orders.edit']);
    $this->actingAs($this->admin, 'admin');

    $order = UnifiedOrder::factory()->create(['status' => 'completed']);

    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'status' => 'pending', // Invalid transition
    ]);

    $response->assertSessionHasErrors();
});
