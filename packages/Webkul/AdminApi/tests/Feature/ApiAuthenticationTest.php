<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Passport\ClientRepository;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;

it('should return access token with valid credentials', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('password')]);

    $clientRepo = new ClientRepository;
    $client = $clientRepo->createPasswordGrantClient(
        $admin->id, 'Auth Test Client', env('APP_URL'), 'admins'
    );

    Apikey::factory()->create([
        'permission_type' => 'all',
        'admin_id'        => $admin->id,
        'oauth_client_id' => $client->getKey(),
    ]);

    $this->postJson('/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => $client->id,
        'client_secret' => $client->plainSecret,
        'username'      => $admin->email,
        'password'      => 'password',
        'scope'         => '',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token',
        ]);
});

it('should return error with invalid credentials', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('password')]);

    $clientRepo = new ClientRepository;
    $client = $clientRepo->createPasswordGrantClient(
        $admin->id, 'Auth Test Client', env('APP_URL'), 'admins'
    );

    Apikey::factory()->create([
        'permission_type' => 'all',
        'admin_id'        => $admin->id,
        'oauth_client_id' => $client->getKey(),
    ]);

    $this->postJson('/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => $client->id,
        'client_secret' => $client->plainSecret,
        'username'      => $admin->email,
        'password'      => 'wrongpassword',
        'scope'         => '',
    ])
        ->assertStatus(400);
});

it('should return error with invalid client credentials', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('password')]);

    $this->postJson('/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => '00000000-0000-0000-0000-000000000000',
        'client_secret' => 'invalid-secret',
        'username'      => $admin->email,
        'password'      => 'password',
        'scope'         => '',
    ])
        ->assertStatus(401);
});

it('should return 401 when no token is provided', function () {
    $this->json('GET', route('admin.api.locales.index'), [], [
        'Accept' => 'application/json',
    ])
        ->assertUnauthorized();
});

it('should return 401 with invalid bearer token', function () {
    $this->json('GET', route('admin.api.locales.index'), [], [
        'Authorization' => 'Bearer invalid-token-string',
        'Accept'        => 'application/json',
    ])
        ->assertUnauthorized();
});

it('should return 406 when Accept header is not application/json', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders([
        'Authorization' => $headers['Authorization'],
        'Accept'        => 'text/html',
    ])->get(route('admin.api.locales.index'))
        ->assertStatus(406);
});

it('should return access token with refresh token', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('password')]);

    $clientRepo = new ClientRepository;
    $client = $clientRepo->createPasswordGrantClient(
        $admin->id, 'Auth Test Client', env('APP_URL'), 'admins'
    );

    Apikey::factory()->create([
        'permission_type' => 'all',
        'admin_id'        => $admin->id,
        'oauth_client_id' => $client->getKey(),
    ]);

    $tokenResponse = $this->postJson('/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => $client->id,
        'client_secret' => $client->plainSecret,
        'username'      => $admin->email,
        'password'      => 'password',
        'scope'         => '',
    ])->json();

    $this->postJson('/oauth/token', [
        'grant_type'    => 'refresh_token',
        'client_id'     => $client->id,
        'client_secret' => $client->plainSecret,
        'refresh_token' => $tokenResponse['refresh_token'],
        'scope'         => '',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token_type',
            'expires_in',
            'access_token',
            'refresh_token',
        ]);
});
