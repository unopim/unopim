<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;
use Webkul\User\Tests\Concerns\UserAssertions;

// admin.configuration.integrations.* are session-guarded web routes. The
// AdminApi test directory is bound to ApiTestCase (OAuth token auth) in
// tests/Pest.php, so mix in UserAssertions here for loginAsAdmin().
uses(UserAssertions::class);

it('returns the robot username when generating an api key', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $robot = Admin::factory()->create([
        'type'  => 'api',
        'email' => 'shopify-robot@api.local',
    ]);

    $apiKey = Apikey::factory()->create([
        'admin_id'        => $robot->id,
        'permission_type' => 'all',
    ]);

    $response = $this->post(route('admin.configuration.integrations.generate_key'), [
        'name'  => $apiKey->name,
        'apiId' => $apiKey->id,
    ]);

    $response->assertOk()
        ->assertJson(['username' => 'shopify-robot@api.local']);
});

it('refuses to generate a key when the bound admin is a human', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $human = Admin::factory()->create([
        'type'  => 'user',
        'email' => 'human-admin@example.local',
    ]);

    $apiKey = Apikey::factory()->create([
        'admin_id'        => $human->id,
        'permission_type' => 'all',
    ]);

    $response = $this->post(route('admin.configuration.integrations.generate_key'), [
        'name'  => $apiKey->name,
        'apiId' => $apiKey->id,
    ]);

    $response->assertNotFound();
});

it('flashes one-time robot credentials to the session on store', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Woocommerce',
        'permission_type' => 'all',
    ]);

    $response->assertSessionHas('api_credentials', function (array $credentials) {
        return ! empty($credentials['username'])
            && ! empty($credentials['password']);
    });
});

it('renders the one-time robot password as static text, not a submittable form field', function () {
    $this->loginAsAdmin();

    $robot = Admin::factory()->create([
        'type'  => 'api',
        'email' => 'shopify-robot@api.local',
    ]);

    $apiKey = Apikey::factory()->create([
        'admin_id'        => $robot->id,
        'permission_type' => 'all',
    ]);

    session(['api_credentials' => [
        'username' => 'x@api.local',
        'password' => 'SECRETVALUE123',
    ]]);

    $response = $this->get(route('admin.configuration.integrations.edit', $apiKey->id));

    $response->assertOk()
        ->assertSee('SECRETVALUE123')
        ->assertDontSee('name="one_time_api_password"', false)
        ->assertDontSee('name="one_time_api_username"', false);
});
