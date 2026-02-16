<?php

use Webkul\ChannelConnector\Models\ChannelConnector;

it('scopes connectors to current tenant', function () {
    $this->loginAsAdmin();

    // Create connector in current tenant
    ChannelConnector::create([
        'code'         => 'tenant-1-store',
        'name'         => 'Tenant 1 Store',
        'channel_type' => 'shopify',
        'credentials'  => [],
        'status'       => 'connected',
    ]);

    $connectors = ChannelConnector::all();

    expect($connectors)->each(function ($connector) {
        $connector->tenant_id->not->toBeNull();
    });
});

it('enforces tenant-scoped unique code constraint', function () {
    $this->loginAsAdmin();

    ChannelConnector::create([
        'code'         => 'unique-test',
        'name'         => 'First',
        'channel_type' => 'shopify',
        'credentials'  => [],
        'status'       => 'connected',
    ]);

    expect(fn () => ChannelConnector::create([
        'code'         => 'unique-test',
        'name'         => 'Duplicate',
        'channel_type' => 'salla',
        'credentials'  => [],
        'status'       => 'disconnected',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
