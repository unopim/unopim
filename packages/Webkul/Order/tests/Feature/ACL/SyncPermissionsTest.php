<?php

use Webkul\Order\Models\OrderSyncLog;
use Webkul\User\Models\Admin;

it('denies access to sync dashboard without permission', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.index'));

    $response->assertStatus(403);
});

it('allows access to sync dashboard with view permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.sync.view']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.index'));

    $response->assertStatus(200);
});

it('denies manual sync without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.sync.view');
    $this->actingAs($admin, 'admin');

    $channel = $this->createTestChannel();

    $response = $this->post(route('admin.order.sync.manual'), [
        'channel_id' => $channel->id,
    ]);

    $response->assertStatus(403);
});

it('allows manual sync with manual-sync permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.sync.manual-sync']);
    $this->actingAs($admin, 'admin');

    $channel = $this->createTestChannel();

    $response = $this->post(route('admin.order.sync.manual'), [
        'channel_id' => $channel->id,
    ]);

    $response->assertStatus([200, 302]);
});

it('denies access to sync logs without permission', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.logs'));

    $response->assertStatus(403);
});

it('allows access to sync logs with logs permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.sync.logs']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.logs'));

    $response->assertStatus(200);
});

it('denies sync retry without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.sync.view');
    $this->actingAs($admin, 'admin');

    $log = OrderSyncLog::factory()->create(['status' => 'failed']);

    $response = $this->post(route('admin.order.sync.retry', $log->id));

    $response->assertStatus(403);
});

it('allows sync retry with retry permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.sync.retry']);
    $this->actingAs($admin, 'admin');

    $log = OrderSyncLog::factory()->create(['status' => 'failed']);

    $response = $this->post(route('admin.order.sync.retry', $log->id));

    $response->assertStatus([200, 302]);
});

it('denies access to sync schedule without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.sync.view');
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.schedule'));

    $response->assertStatus(403);
});

it('allows access to sync schedule with schedule permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.sync.schedule']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.schedule'));

    $response->assertStatus(200);
});

it('denies sync settings access without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.sync.view');
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.settings'));

    $response->assertStatus(403);
});

it('allows sync settings access with settings permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.sync.settings']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.sync.settings'));

    $response->assertStatus(200);
});
