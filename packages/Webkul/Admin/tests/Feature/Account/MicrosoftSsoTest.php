<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;

it('logs in an existing admin via microsoft sso using email match', function () {
    config()->set('services.microsoft_sso.enabled', true);
    config()->set('services.microsoft_sso.tenant', 'common');
    config()->set('services.microsoft_sso.client_id', 'client-id');
    config()->set('services.microsoft_sso.client_secret', 'client-secret');

    $admin = Admin::factory()->create([
        'email'    => 'sso-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    Http::fake([
        'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
            'access_token' => 'access-token',
        ], 200),
        'https://graph.microsoft.com/v1.0/me*' => Http::response([
            'mail'              => 'sso-user@example.com',
            'userPrincipalName' => 'sso-user@example.com',
        ], 200),
    ]);

    $response = $this->withSession(['microsoft_sso_state' => 'valid-state'])
        ->get(route('admin.session.microsoft.callback', [
            'state' => 'valid-state',
            'code'  => 'auth-code',
        ]));

    $response->assertRedirect(route('admin.dashboard.index'));
    $this->assertAuthenticatedAs($admin, 'admin');
});

it('does not auto-create user when microsoft sso email is unknown', function () {
    config()->set('services.microsoft_sso.enabled', true);
    config()->set('services.microsoft_sso.tenant', 'common');
    config()->set('services.microsoft_sso.client_id', 'client-id');
    config()->set('services.microsoft_sso.client_secret', 'client-secret');

    Http::fake([
        'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
            'access_token' => 'access-token',
        ], 200),
        'https://graph.microsoft.com/v1.0/me*' => Http::response([
            'mail'              => 'unknown@example.com',
            'userPrincipalName' => 'unknown@example.com',
        ], 200),
    ]);

    $response = $this->withSession(['microsoft_sso_state' => 'valid-state'])
        ->get(route('admin.session.microsoft.callback', [
            'state' => 'valid-state',
            'code'  => 'auth-code',
        ]));

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionHas('error');

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'email' => 'unknown@example.com',
    ]);
});

it('shows microsoft sign-in button only when sso is enabled', function () {
    config()->set('services.microsoft_sso.enabled', false);

    get(route('admin.session.create'))
        ->assertStatus(200)
        ->assertDontSeeText('Sign in with Microsoft');

    config()->set('services.microsoft_sso.enabled', true);
    config()->set('services.microsoft_sso.client_id', 'client-id');
    config()->set('services.microsoft_sso.client_secret', 'client-secret');
    config()->set('services.microsoft_sso.tenant', 'common');

    get(route('admin.session.create'))
        ->assertStatus(200)
        ->assertSeeText('Sign in with Microsoft');
});
