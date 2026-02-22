<?php

use Webkul\Order\Models\OrderWebhook;
use Webkul\User\Models\Admin;

it('denies access to webhooks index without permission', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.webhooks.index'));

    $response->assertStatus(403);
});

it('allows access to webhooks index with view permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.view']);
    $this->actingAs($admin, 'admin');

    $response = $this->get(route('admin.order.webhooks.index'));

    $response->assertStatus(200);
});

it('denies webhook creation without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.webhooks.view');
    $this->actingAs($admin, 'admin');

    $response = $this->post(route('admin.order.webhooks.store'), []);

    $response->assertStatus(403);
});

it('allows webhook creation with create permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.create']);
    $this->actingAs($admin, 'admin');

    $channel = $this->createTestChannel();

    $response = $this->post(route('admin.order.webhooks.store'), [
        'channel_id' => $channel->id,
        'event_types' => ['order.created'],
        'is_active' => true,
    ]);

    $response->assertStatus([200, 302]);
});

it('denies webhook editing without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.webhooks.view');
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->put(route('admin.order.webhooks.update', $webhook->id), [
        'event_types' => ['order.updated'],
    ]);

    $response->assertStatus(403);
});

it('allows webhook editing with edit permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.edit']);
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->put(route('admin.order.webhooks.update', $webhook->id), [
        'event_types' => ['order.updated'],
    ]);

    $response->assertStatus([200, 302]);
});

it('denies webhook deletion without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.webhooks.view');
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->delete(route('admin.order.webhooks.destroy', $webhook->id));

    $response->assertStatus(403);
});

it('allows webhook deletion with delete permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.delete']);
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->delete(route('admin.order.webhooks.destroy', $webhook->id));

    $response->assertStatus([200, 302]);
});

it('denies webhook testing without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.webhooks.view');
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->post(route('admin.order.webhooks.test', $webhook->id));

    $response->assertStatus(403);
});

it('allows webhook testing with test permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.test']);
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->post(route('admin.order.webhooks.test', $webhook->id));

    $response->assertStatus([200, 302]);
});

it('denies access to webhook logs without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.webhooks.view');
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->get(route('admin.order.webhooks.logs', $webhook->id));

    $response->assertStatus(403);
});

it('allows access to webhook logs with logs permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.logs']);
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->get(route('admin.order.webhooks.logs', $webhook->id));

    $response->assertStatus(200);
});

it('denies webhook retry without permission', function () {
    $admin = Admin::factory()->create();
    bouncer()->allow($admin)->to('order.webhooks.view');
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->post(route('admin.order.webhooks.retry', $webhook->id));

    $response->assertStatus(403);
});

it('allows webhook retry with retry permission', function () {
    $admin = $this->createAdminWithOrderPermissions(['order.webhooks.retry']);
    $this->actingAs($admin, 'admin');

    $webhook = OrderWebhook::factory()->create();

    $response = $this->post(route('admin.order.webhooks.retry', $webhook->id));

    $response->assertStatus([200, 302]);
});
