<?php

use Webkul\User\Models\Admin;

it('denies access to profitability dashboard without permission', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.profitability.index'));

    $response->assertStatus(403);
});

it('allows access to profitability dashboard with view permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.view']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.profitability.index'));

    $response->assertStatus(200);
});

it('hides cost data without view-costs permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.view']);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertDontSee('600.00'); // Cost should be hidden
});

it('shows cost data with view-costs permission', function () {
    $admin = $this->createAdminWithOrderPermissions([
        'order.profitability.view',
        'order.profitability.view-costs',
    ]);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertSee('600.00'); // Cost should be visible
});

it('hides margin data without view-margins permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.view']);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertDontSee('40.00%'); // Margin should be hidden
});

it('shows margin data with view-margins permission', function () {
    $admin = $this->createAdminWithOrderPermissions([
        'order.profitability.view',
        'order.profitability.view-margins',
    ]);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    $response->assertStatus(200)
        ->assertSee('40.00'); // Margin should be visible
});

it('denies channel comparison without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.profitability.view');
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.profitability.channel-comparison'));

    $response->assertStatus(403);
});

it('allows channel comparison with channel-comparison permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.channel-comparison']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.profitability.channel-comparison'));

    $response->assertStatus(200);
});

it('denies profitability export without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.profitability.view');
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.profitability.export'));

    $response->assertStatus(403);
});

it('allows profitability export with export permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.export']);
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.profitability.export'));

    $response->assertStatus(200);
});

it('denies profitability settings access without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.profitability.view');
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.profitability.settings'));

    $response->assertStatus(403);
});

it('allows profitability settings access with settings permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.settings']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.profitability.settings'));

    $response->assertStatus(200);
});

it('enforces granular profitability permissions', function () {
    // Admin with only view permission
    $admin = $this->createAdminWithOrderPermissions(['order.profitability.view']);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    // Can view dashboard
    $response->assertStatus(200);

    // But costs and margins are hidden
    $response->assertDontSee('600.00') // Cost
        ->assertDontSee('40.00'); // Margin percentage
});

it('allows full profitability access with all permissions', function () {
    $admin = $this->createAdminWithOrderPermissions([
        'order.profitability.view',
        'order.profitability.view-costs',
        'order.profitability.view-margins',
        'order.profitability.channel-comparison',
        'order.profitability.export',
        'order.profitability.settings',
    ]);
    $this->actingAs($admin, 'admin');

    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    $response = $this->get(route('admin.order.profitability.show', $order->id));

    // Can see everything
    $response->assertStatus(200)
        ->assertSee('1000.00') // Revenue
        ->assertSee('600.00') // Cost
        ->assertSee('400.00') // Profit
        ->assertSee('40.00'); // Margin
});
