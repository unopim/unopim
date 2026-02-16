<?php

use Illuminate\Support\Facades\Event;
use Webkul\ChannelConnector\Events\ConnectorCreated;
use Webkul\ChannelConnector\Events\ConnectorCreating;
use Webkul\ChannelConnector\Events\ConnectorDeleted;
use Webkul\ChannelConnector\Events\ConnectorDeleting;
use Webkul\ChannelConnector\Events\ConnectorUpdated;
use Webkul\ChannelConnector\Events\ConnectorUpdating;
use Webkul\ChannelConnector\Models\ChannelConnector;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('dispatches ConnectorCreating and ConnectorCreated events on store', function () {
    Event::fake([ConnectorCreating::class, ConnectorCreated::class]);

    $this->post(route('admin.channel_connector.connectors.store'), [
        'code'         => 'event-test',
        'name'         => 'Event Test',
        'channel_type' => 'shopify',
        'credentials'  => ['access_token' => 'test', 'shop_url' => 'test.myshopify.com'],
    ]);

    Event::assertDispatched(ConnectorCreating::class);
    Event::assertDispatched(ConnectorCreated::class);
});

it('dispatches ConnectorUpdating and ConnectorUpdated events on update', function () {
    Event::fake([ConnectorUpdating::class, ConnectorUpdated::class]);

    $connector = ChannelConnector::create([
        'code'         => 'update-event',
        'name'         => 'Update Event',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => ['access_token' => 'test'],
    ]);

    $this->put(route('admin.channel_connector.connectors.update', $connector->code), [
        'name'     => 'Updated Name',
        'settings' => ['sync_direction' => 'bidirectional'],
    ]);

    Event::assertDispatched(ConnectorUpdating::class);
    Event::assertDispatched(ConnectorUpdated::class);
});

it('dispatches ConnectorDeleting and ConnectorDeleted events on destroy', function () {
    Event::fake([ConnectorDeleting::class, ConnectorDeleted::class]);

    $connector = ChannelConnector::create([
        'code'         => 'delete-event',
        'name'         => 'Delete Event',
        'channel_type' => 'shopify',
        'status'       => 'disconnected',
        'credentials'  => ['access_token' => 'test'],
    ]);

    $this->delete(route('admin.channel_connector.connectors.destroy', $connector->code));

    Event::assertDispatched(ConnectorDeleting::class);
    Event::assertDispatched(ConnectorDeleted::class);
});
