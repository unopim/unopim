<?php

use Webkul\Order\Models\UnifiedOrder;
use Webkul\User\Models\Admin;

it('denies access to orders index without permission', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.orders.index'));

    $response->assertStatus(403);
});

it('allows access to orders index with view permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.view']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.orders.index'));

    $response->assertStatus(200);
});

it('denies order creation without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.orders.store'), []);

    $response->assertStatus(403);
});

it('allows order creation with create permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.create']);
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.orders.store'), [
        'order_number' => 'TEST-001',
        'status' => 'pending',
        'total_amount' => 100.00,
    ]);

    // May have validation errors but should not be 403
    $response->assertStatus([200, 302, 422]);
});

it('denies order editing without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'status' => 'processing',
    ]);

    $response->assertStatus(403);
});

it('allows order editing with edit permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.edit']);
    $this->actingAs($admin, 'admin');

    $order = UnifiedOrder::factory()->create(['status' => 'pending']);

    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'status' => 'processing',
    ]);

    $response->assertStatus([200, 302]);
});

it('denies order deletion without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    $response = $this->delete(route('admin.order.orders.destroy', $order->id));

    $response->assertStatus(403);
});

it('allows order deletion with delete permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.delete']);
    $this->actingAs($admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    $response = $this->delete(route('admin.order.orders.destroy', $order->id));

    $response->assertStatus([200, 302]);
});

it('denies mass update without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.orders.mass-update'), []);

    $response->assertStatus(403);
});

it('allows mass update with mass-update permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.mass-update']);
    $this->actingAs($admin, 'admin');

    $orders = UnifiedOrder::factory()->count(3)->create();

    $response = $this->post(route('admin.order.orders.mass-update'), [
        'indices' => $orders->pluck('id')->toArray(),
        'status' => 'processing',
    ]);

    $response->assertStatus([200, 302]);
});

it('denies mass delete without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.orders.mass-destroy'), []);

    $response->assertStatus(403);
});

it('allows mass delete with mass-delete permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.mass-delete']);
    $this->actingAs($admin, 'admin');

    $orders = UnifiedOrder::factory()->count(3)->create();

    $response = $this->post(route('admin.order.orders.mass-destroy'), [
        'indices' => $orders->pluck('id')->toArray(),
    ]);

    $response->assertStatus([200, 302]);
});

it('denies export without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.orders.export'));

    $response->assertStatus(403);
});

it('allows export with export permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.orders.export']);
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.orders.export'));

    $response->assertStatus(200);
});

it('enforces hierarchical permissions correctly', function () {
    $admin = Admin::factory()->create();
    // View permission doesn't grant edit access
    bouncer()->allow($admin)->to('order.orders.view');
    $this->actingAs($admin, 'admin');

    $order = UnifiedOrder::factory()->create();

    // Can view
    $response = $this->get(route('admin.order.orders.show', $order->id));
    $response->assertStatus(200);

    // Cannot edit
    $response = $this->put(route('admin.order.orders.update', $order->id), [
        'status' => 'processing',
    ]);
    $response->assertStatus(403);
});
