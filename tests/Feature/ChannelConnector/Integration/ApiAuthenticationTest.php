<?php

use Webkul\ChannelConnector\Models\ChannelConnector;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('rejects unauthenticated API requests with 401', function () {
    $routes = [
        ['GET', '/api/v1/rest/channel-connectors'],
        ['POST', '/api/v1/rest/channel-connectors'],
    ];

    foreach ($routes as [$method, $uri]) {
        $response = $this->json($method, $uri);
        expect($response->getStatusCode())->toBe(401, "Expected 401 for {$method} {$uri}");
    }
});

it('rejects API requests with invalid bearer token', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-token-12345',
    ])->getJson('/api/v1/rest/channel-connectors');

    $response->assertStatus(401);
});

it('allows API requests with valid OAuth token', function () {
    $token = $this->createAdminApiToken();

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->getJson('/api/v1/rest/channel-connectors');

    $response->assertSuccessful();
});

it('never exposes credentials in API connector listing', function () {
    $token = $this->createAdminApiToken();

    ChannelConnector::create([
        'code'         => 'api-cred-test',
        'name'         => 'API Cred Test',
        'channel_type' => 'shopify',
        'status'       => 'connected',
        'credentials'  => [
            'access_token' => 'shpat_super_secret_token',
            'shop_url'     => 'test.myshopify.com',
        ],
    ]);

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->getJson('/api/v1/rest/channel-connectors');

    $response->assertSuccessful();
    $content = $response->getContent();

    // Credentials must never appear in API response
    expect($content)->not->toContain('shpat_super_secret_token');
    expect($content)->not->toContain('access_token');
});
