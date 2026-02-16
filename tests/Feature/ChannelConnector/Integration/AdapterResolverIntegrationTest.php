<?php

use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Services\AdapterResolver;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('resolves all registered adapter types from the container', function () {
    $resolver = app(AdapterResolver::class);
    $types = $resolver->getRegisteredTypes();

    // All three adapters should be registered via ModuleServiceProviders
    expect($types)->toContain('shopify');
    expect($types)->toContain('salla');
    expect($types)->toContain('easy_orders');
});

it('resolves a Shopify adapter with credentials set', function () {
    $resolver = app(AdapterResolver::class);

    $connector = ChannelConnector::create([
        'code'         => 'shopify-test',
        'name'         => 'Shopify Test',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => [
            'access_token' => 'shpat_test_token',
            'shop_url'     => 'test.myshopify.com',
        ],
    ]);

    $adapter = $resolver->resolve($connector);

    expect($adapter)->toBeInstanceOf(ChannelAdapterContract::class);
});

it('resolves a Salla adapter with credentials set', function () {
    $resolver = app(AdapterResolver::class);

    $connector = ChannelConnector::create([
        'code'         => 'salla-test',
        'name'         => 'Salla Test',
        'channel_type' => 'salla',
        'status'       => 'connected',
        'credentials'  => [
            'access_token'  => 'test-salla-token',
            'refresh_token' => 'test-refresh',
            'client_id'     => 'client-123',
            'client_secret' => 'secret-456',
        ],
    ]);

    $adapter = $resolver->resolve($connector);

    expect($adapter)->toBeInstanceOf(ChannelAdapterContract::class);
});

it('throws exception for unregistered channel type', function () {
    $resolver = app(AdapterResolver::class);

    $connector = ChannelConnector::create([
        'code'         => 'unknown-test',
        'name'         => 'Unknown Test',
        'channel_type' => 'nonexistent_platform',
        'status'       => 'disconnected',
        'credentials'  => [],
    ]);

    $resolver->resolve($connector);
})->throws(\InvalidArgumentException::class);

it('resolves adapter by type without a connector model', function () {
    $resolver = app(AdapterResolver::class);

    $adapter = $resolver->resolveByType('shopify', [
        'access_token' => 'test-token',
        'shop_url'     => 'test.myshopify.com',
    ]);

    expect($adapter)->toBeInstanceOf(ChannelAdapterContract::class);
});
