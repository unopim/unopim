<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Passport\ClientRepository;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;

/*
 * A deactivated admin must not obtain an OAuth token through the password grant.
 * The status guard uses a truthiness check (not a strict === 0) so a driver that
 * returns the column as the string "0" under emulated prepares is still rejected.
 */
function passwordGrantClientFor(Admin $admin): object
{
    $client = (new ClientRepository)->createPasswordGrantClient('Auth Test Client', 'admins', confidential: true);

    $client->forceFill([
        'user_id'    => $admin->id,
        'owner_type' => Admin::class,
        'owner_id'   => $admin->id,
    ])->save();

    Apikey::factory()->create([
        'permission_type' => 'all',
        'admin_id'        => $admin->id,
        'oauth_client_id' => $client->getKey(),
    ]);

    return $client;
}

it('denies a password-grant token to a deactivated admin', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('password'), 'status' => 0]);
    $client = passwordGrantClientFor($admin);

    $this->postJson('/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => $client->id,
        'client_secret' => $client->plainSecret,
        'username'      => $admin->email,
        'password'      => 'password',
        'scope'         => '',
    ])->assertStatus(400);
});

it('issues a password-grant token to an active admin', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create(['password' => bcrypt('password'), 'status' => 1]);
    $client = passwordGrantClientFor($admin);

    $this->postJson('/oauth/token', [
        'grant_type'    => 'password',
        'client_id'     => $client->id,
        'client_secret' => $client->plainSecret,
        'username'      => $admin->email,
        'password'      => 'password',
        'scope'         => '',
    ])->assertOk()->assertJsonStructure(['access_token']);
});
