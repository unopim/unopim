<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Token;
use Webkul\AdminApi\Models\Apikey;
use Webkul\AdminApi\Models\Client;
use Webkul\User\Models\Admin;
use Webkul\User\Tests\Concerns\UserAssertions;

// admin.configuration.integrations.* are session-guarded web routes. The
// AdminApi test directory is bound to ApiTestCase (OAuth token auth) in
// tests/Pest.php, so mix in UserAssertions here for loginAsAdmin().
uses(UserAssertions::class);

it('regenerates the robot password and revokes existing tokens', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $robot = Admin::factory()->create([
        'type'     => 'api',
        'email'    => 'shopify-robot@api.local',
        'password' => Hash::make('old-password'),
    ]);

    $client = Client::create([
        'user_id'                => $robot->id,
        'name'                   => 'Shopify robot client',
        'secret'                 => Str::random(40),
        'provider'               => 'admins',
        'redirect'               => '',
        'personal_access_client' => false,
        'password_client'        => true,
        'revoked'                => false,
    ]);

    $apiKey = Apikey::factory()->create([
        'admin_id'        => $robot->id,
        'oauth_client_id' => $client->getKey(),
        'permission_type' => 'all',
    ]);

    $token = Token::create([
        'id'        => Str::random(80),
        'user_id'   => $robot->id,
        'client_id' => $client->getKey(),
        'name'      => $apiKey->name,
        'scopes'    => [],
        'revoked'   => false,
    ]);

    $response = $this->post(route('admin.configuration.integrations.re_generate_password'), [
        'apiId' => $apiKey->id,
    ]);

    $response->assertOk()
        ->assertJson(['username' => 'shopify-robot@api.local']);

    $password = $response->json('password');

    expect($password)->not->toBeEmpty()
        ->and(strlen($password))->toBe(32);

    $robot->refresh();

    expect(Hash::check('old-password', $robot->password))->toBeFalse()
        ->and(Hash::check($password, $robot->password))->toBeTrue();

    expect($token->fresh()->revoked)->toBeTrue();
});
