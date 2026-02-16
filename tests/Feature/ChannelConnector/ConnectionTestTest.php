<?php

use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\ChannelConnector\ValueObjects\ConnectionResult;

it('can test connection successfully with mock adapter', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'test-conn',
        'name'         => 'Test Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'test.myshopify.com', 'access_token' => 'token'],
        'status'       => 'disconnected',
    ]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('setCredentials')->andReturnSelf();
    $mockAdapter->shouldReceive('testConnection')->andReturn(
        new ConnectionResult(
            success: true,
            message: 'Connection verified',
            channelInfo: ['store_name' => 'Test Store', 'product_count' => 100],
        )
    );

    // Mock the AdapterResolver to return our mock adapter
    $mockResolver = Mockery::mock(AdapterResolver::class);
    $mockResolver->shouldReceive('resolve')
        ->once()
        ->with(Mockery::on(fn ($arg) => $arg instanceof ChannelConnector && $arg->code === 'test-conn'))
        ->andReturn($mockAdapter);

    $this->app->instance(AdapterResolver::class, $mockResolver);

    $response = $this->postJson(route('admin.channel_connector.connectors.test', $connector->code));

    $response->assertOk()
        ->assertJson(['success' => true]);
});

it('returns failure when connection test fails', function () {
    $this->loginAsAdmin();

    $connector = ChannelConnector::create([
        'code'         => 'fail-conn',
        'name'         => 'Fail Store',
        'channel_type' => 'shopify',
        'credentials'  => ['shop_url' => 'bad.myshopify.com'],
        'status'       => 'disconnected',
    ]);

    $mockAdapter = Mockery::mock(ChannelAdapterContract::class);
    $mockAdapter->shouldReceive('setCredentials')->andReturnSelf();
    $mockAdapter->shouldReceive('testConnection')->andReturn(
        new ConnectionResult(
            success: false,
            message: 'Invalid credentials',
            errors: ['Invalid access token'],
        )
    );

    $mockResolver = Mockery::mock(AdapterResolver::class);
    $mockResolver->shouldReceive('resolve')
        ->once()
        ->andReturn($mockAdapter);

    $this->app->instance(AdapterResolver::class, $mockResolver);

    $response = $this->postJson(route('admin.channel_connector.connectors.test', $connector->code));

    $response->assertOk()
        ->assertJson(['success' => false]);
});
