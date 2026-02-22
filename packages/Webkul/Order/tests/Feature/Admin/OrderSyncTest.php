<?php

use Webkul\Order\Models\OrderSyncLog;
use Webkul\Order\Services\OrderSyncService;

beforeEach(function () {
    $this->admin = $this->createAdminWithOrderPermissions([
        'order.sync.view',
        'order.sync.manual-sync',
        'order.sync.logs',
    ]);
    $this->actingAs($this->admin, 'admin');
    $this->channel = $this->createTestChannel();
});

it('can view sync dashboard', function () {
    $response = $this->get(route('admin.order.sync.index'));

    $response->assertStatus(200)
        ->assertViewIs('order::sync.index');
});

it('can manually trigger channel sync', function () {
    $this->mock(OrderSyncService::class, function ($mock) {
        $mock->shouldReceive('syncChannel')
            ->andReturn(['status' => 'success', 'synced_count' => 10]);
    });

    $response = $this->post(route('admin.order.sync.manual'), [
        'channel_id' => $this->channel->id,
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
});

it('can sync with date range', function () {
    $this->mock(OrderSyncService::class, function ($mock) {
        $mock->shouldReceive('syncChannel')
            ->andReturn(['status' => 'success', 'synced_count' => 5]);
    });

    $response = $this->post(route('admin.order.sync.manual'), [
        'channel_id' => $this->channel->id,
        'date_from' => now()->subDays(7)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
});

it('can view sync logs', function () {
    OrderSyncLog::factory()->count(10)->create(['channel_id' => $this->channel->id]);

    $response = $this->get(route('admin.order.sync.logs'));

    $response->assertStatus(200)
        ->assertViewIs('order::sync.logs')
        ->assertViewHas('logs');
});

it('can filter sync logs by channel', function () {
    OrderSyncLog::factory()->count(5)->create(['channel_id' => $this->channel->id]);

    $response = $this->get(route('admin.order.sync.logs', ['channel_id' => $this->channel->id]));

    $response->assertStatus(200);
});

it('can filter sync logs by status', function () {
    OrderSyncLog::factory()->count(3)->create(['status' => 'completed']);
    OrderSyncLog::factory()->count(2)->create(['status' => 'failed']);

    $response = $this->get(route('admin.order.sync.logs', ['status' => 'failed']));

    $response->assertStatus(200);
});

it('can view sync log details', function () {
    $log = OrderSyncLog::factory()->create(['channel_id' => $this->channel->id]);

    $response = $this->get(route('admin.order.sync.logs.show', $log->id));

    $response->assertStatus(200)
        ->assertViewIs('order::sync.log-details')
        ->assertViewHas('log');
});

it('can retry failed sync', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.sync.retry']);
    $this->actingAs($this->admin, 'admin');

    $log = OrderSyncLog::factory()->create([
        'status' => 'failed',
        'channel_id' => $this->channel->id,
    ]);

    $this->mock(OrderSyncService::class, function ($mock) {
        $mock->shouldReceive('retrySyncLog')
            ->andReturn(['status' => 'success']);
    });

    $response = $this->post(route('admin.order.sync.retry', $log->id));

    $response->assertRedirect()
        ->assertSessionHas('success');
});

it('can configure sync schedule', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.sync.schedule']);
    $this->actingAs($this->admin, 'admin');

    $response = $this->get(route('admin.order.sync.schedule'));

    $response->assertStatus(200)
        ->assertViewIs('order::sync.schedule');
});

it('can save sync schedule settings', function () {
    $this->admin = $this->createAdminWithOrderPermissions(['order.sync.schedule']);
    $this->actingAs($this->admin, 'admin');

    $response = $this->post(route('admin.order.sync.schedule.save'), [
        'channel_id' => $this->channel->id,
        'frequency' => 'hourly',
        'enabled' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');
});

it('displays sync statistics', function () {
    OrderSyncLog::factory()->count(10)->create([
        'channel_id' => $this->channel->id,
        'status' => 'completed',
    ]);
    OrderSyncLog::factory()->count(2)->create([
        'channel_id' => $this->channel->id,
        'status' => 'failed',
    ]);

    $response = $this->get(route('admin.order.sync.index'));

    $response->assertStatus(200)
        ->assertSee('10') // Completed count
        ->assertSee('2'); // Failed count
});

it('validates channel selection for sync', function () {
    $response = $this->post(route('admin.order.sync.manual'), [
        'channel_id' => null,
    ]);

    $response->assertSessionHasErrors('channel_id');
});

it('validates date range for sync', function () {
    $response = $this->post(route('admin.order.sync.manual'), [
        'channel_id' => $this->channel->id,
        'date_from' => now()->format('Y-m-d'),
        'date_to' => now()->subDays(7)->format('Y-m-d'), // Invalid range
    ]);

    $response->assertSessionHasErrors();
});

it('shows sync progress indicator', function () {
    $log = OrderSyncLog::factory()->create([
        'status' => 'running',
        'channel_id' => $this->channel->id,
    ]);

    $response = $this->get(route('admin.order.sync.index'));

    $response->assertStatus(200)
        ->assertSee('running');
});
