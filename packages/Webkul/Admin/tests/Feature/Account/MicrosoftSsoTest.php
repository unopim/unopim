<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;

function microsoftSsoConfig(string $tenant = 'tenant-id'): void
{
    config()->set('services.microsoft_sso.enabled', true);
    config()->set('services.microsoft_sso.tenant', $tenant);
    config()->set('services.microsoft_sso.client_id', 'client-id');
    config()->set('services.microsoft_sso.client_secret', 'client-secret');
}

function ssoJwt(array $claims): string
{
    $segment = fn (array $part): string => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');

    return $segment(['alg' => 'none', 'typ' => 'JWT']).'.'.$segment($claims).'.signature';
}

function ssoHandshake(array $overrides = []): array
{
    return ['microsoft' => array_merge([
        'state'    => 'valid-state',
        'verifier' => 'valid-verifier',
        'nonce'    => 'valid-nonce',
    ], $overrides)];
}

function fakeMicrosoftEndpoints(array $profile, array $tokenClaims = []): void
{
    Http::fake([
        'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
            'access_token' => 'access-token',
            'id_token'     => ssoJwt(array_merge(['nonce' => 'valid-nonce', 'tid' => 'tenant-id'], $tokenClaims)),
        ], 200),
        'https://graph.microsoft.com/v1.0/me*' => Http::response($profile, 200),
    ]);
}

function callbackWith(array $handshake): TestResponse
{
    return test()->withSession(['sso_handshake' => $handshake])
        ->get(route('admin.session.sso.callback', [
            'provider' => 'microsoft',
            'state'    => 'valid-state',
            'code'     => 'auth-code',
        ]));
}

it('logs in an existing admin via microsoft sso using email match', function () {
    microsoftSsoConfig();

    $admin = Admin::factory()->create([
        'email'    => 'sso-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-1',
        'mail'              => 'sso-user@example.com',
        'userPrincipalName' => 'sso-user@example.com',
    ]);

    callbackWith(ssoHandshake())->assertRedirect(route('admin.dashboard.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('links the provider subject id to the admin on first sign in', function () {
    $admin = Admin::factory()->create([
        'email'    => 'linked-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-linked',
        'mail'              => 'linked-user@example.com',
        'userPrincipalName' => 'linked-user@example.com',
    ]);

    callbackWith(ssoHandshake());

    expect($admin->fresh()->sso_provider)->toBe('microsoft')
        ->and($admin->fresh()->sso_identifier)->toBe('object-id-linked');
});

it('matches on the subject id after the directory changes the email', function () {
    $admin = Admin::factory()->create([
        'email'          => 'old-address@example.com',
        'password'       => Hash::make('password'),
        'status'         => 1,
        'sso_provider'   => 'microsoft',
        'sso_identifier' => 'object-id-stable',
    ]);

    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-stable',
        'mail'              => 'brand-new-address@example.com',
        'userPrincipalName' => 'brand-new-address@example.com',
    ]);

    callbackWith(ssoHandshake());

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('refuses a reassigned email that belongs to another linked subject id', function () {
    Admin::factory()->create([
        'email'          => 'recycled@example.com',
        'password'       => Hash::make('password'),
        'status'         => 1,
        'sso_provider'   => 'microsoft',
        'sso_identifier' => 'object-id-original-holder',
    ]);

    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-new-joiner',
        'mail'              => 'recycled@example.com',
        'userPrincipalName' => 'recycled@example.com',
    ]);

    $response = callbackWith(ssoHandshake());

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionHas('error');

    $this->assertGuest('admin');
});

it('refuses a token whose tenant claim is not the configured tenant', function () {
    Admin::factory()->create([
        'email'    => 'other-tenant@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-foreign',
        'mail'              => 'other-tenant@example.com',
        'userPrincipalName' => 'other-tenant@example.com',
    ], ['tid' => 'a-different-tenant']);

    $response = callbackWith(ssoHandshake());

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('admin');
});

it('refuses a token whose nonce does not match the handshake', function () {
    Admin::factory()->create([
        'email'    => 'replayed@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-replay',
        'mail'              => 'replayed@example.com',
        'userPrincipalName' => 'replayed@example.com',
    ], ['nonce' => 'someone-elses-nonce']);

    $response = callbackWith(ssoHandshake());

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('admin');
});

it('refuses any tenant on a shared authority without an allow list', function () {
    Admin::factory()->create([
        'email'    => 'multi-tenant@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig('common');
    config()->set('services.microsoft_sso.allowed_tenants', '');

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-any',
        'mail'              => 'multi-tenant@example.com',
        'userPrincipalName' => 'multi-tenant@example.com',
    ], ['tid' => 'some-random-tenant']);

    $response = callbackWith(ssoHandshake());

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('admin');
});

it('accepts an allow listed tenant on a shared authority', function () {
    $admin = Admin::factory()->create([
        'email'    => 'allow-listed@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig('common');
    config()->set('services.microsoft_sso.allowed_tenants', 'trusted-tenant, another-tenant');

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-allowed',
        'mail'              => 'allow-listed@example.com',
        'userPrincipalName' => 'allow-listed@example.com',
    ], ['tid' => 'trusted-tenant']);

    callbackWith(ssoHandshake());

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('accepts a tenant configured as a verified domain', function () {
    $admin = Admin::factory()->create([
        'email'    => 'domain-tenant@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig('contoso.onmicrosoft.com');

    Http::fake([
        'https://login.microsoftonline.com/*/v2.0/.well-known/openid-configuration' => Http::response([
            'issuer' => 'https://login.microsoftonline.com/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee/v2.0',
        ], 200),
        'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
            'access_token' => 'access-token',
            'id_token'     => ssoJwt([
                'nonce' => 'valid-nonce',
                'tid'   => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            ]),
        ], 200),
        'https://graph.microsoft.com/v1.0/me*' => Http::response([
            'id'                => 'object-id-domain',
            'mail'              => 'domain-tenant@example.com',
            'userPrincipalName' => 'domain-tenant@example.com',
        ], 200),
    ]);

    callbackWith(ssoHandshake());

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('refuses a foreign tenant when configured with a verified domain', function () {
    Admin::factory()->create([
        'email'    => 'domain-foreign@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    microsoftSsoConfig('contoso.onmicrosoft.com');

    Http::fake([
        'https://login.microsoftonline.com/*/v2.0/.well-known/openid-configuration' => Http::response([
            'issuer' => 'https://login.microsoftonline.com/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee/v2.0',
        ], 200),
        'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
            'access_token' => 'access-token',
            'id_token'     => ssoJwt([
                'nonce' => 'valid-nonce',
                'tid'   => '99999999-9999-9999-9999-999999999999',
            ]),
        ], 200),
        'https://graph.microsoft.com/v1.0/me*' => Http::response([
            'id'                => 'object-id-domain-foreign',
            'mail'              => 'domain-foreign@example.com',
            'userPrincipalName' => 'domain-foreign@example.com',
        ], 200),
    ]);

    $response = callbackWith(ssoHandshake());

    $response->assertRedirect(route('admin.session.create'));
    $this->assertGuest('admin');
});

it('refuses a callback whose stored handshake is malformed', function () {
    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-malformed',
        'mail'              => 'malformed@example.com',
        'userPrincipalName' => 'malformed@example.com',
    ]);

    $response = callbackWith(['microsoft' => ['state' => 'valid-state']]);

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionHas('error');
    $this->assertGuest('admin');
});

it('sends a pkce challenge and stores the verifier', function () {
    microsoftSsoConfig();

    $response = get(route('admin.session.sso.redirect', ['provider' => 'microsoft']));

    $handshake = session('sso_handshake.microsoft');

    expect($handshake['verifier'])->toBeString()->not->toBeEmpty();

    $expected = rtrim(strtr(base64_encode(hash('sha256', $handshake['verifier'], true)), '+/', '-_'), '=');

    $location = $response->headers->get('Location');

    expect($location)->toContain('code_challenge='.urlencode($expected))
        ->toContain('code_challenge_method=S256')
        ->toContain('nonce='.urlencode($handshake['nonce']));
});

it('does not auto-create user when microsoft sso email is unknown', function () {
    microsoftSsoConfig();

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-unknown',
        'mail'              => 'unknown@example.com',
        'userPrincipalName' => 'unknown@example.com',
    ]);

    $response = callbackWith(ssoHandshake());

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
        ->assertDontSeeText(trans('admin::app.users.sessions.sso-sign-in-with-microsoft'));

    config()->set('services.microsoft_sso.enabled', true);

    get(route('admin.session.create'))
        ->assertStatus(200)
        ->assertDontSeeText(trans('admin::app.users.sessions.sso-sign-in-with-microsoft'));

    microsoftSsoConfig();

    get(route('admin.session.create'))
        ->assertStatus(200)
        ->assertSeeText(trans('admin::app.users.sessions.sso-sign-in-with-microsoft'));
});

it('does not allow inactive matched admin via microsoft sso', function () {
    microsoftSsoConfig();

    $admin = Admin::factory()->create([
        'email'    => 'inactive-sso-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 0,
    ]);

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-inactive',
        'mail'              => 'inactive-sso-user@example.com',
        'userPrincipalName' => 'inactive-sso-user@example.com',
    ]);

    $response = callbackWith(ssoHandshake());

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionHas('warning', trans('admin::app.settings.users.activate-warning'));
    $this->assertGuest('admin');

    expect($admin->status)->toBe(0);
});

it('matches microsoft sso email in a case-insensitive way', function () {
    microsoftSsoConfig();

    $admin = Admin::factory()->create([
        'email'    => 'SSO-USER@EXAMPLE.COM',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-case',
        'mail'              => 'sso-user@example.com',
        'userPrincipalName' => 'sso-user@example.com',
    ]);

    callbackWith(ssoHandshake())->assertRedirect(route('admin.dashboard.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('falls back to userPrincipalName when mail is missing', function () {
    microsoftSsoConfig();

    $admin = Admin::factory()->create([
        'email'    => 'principal-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    fakeMicrosoftEndpoints([
        'id'                => 'object-id-upn',
        'mail'              => null,
        'userPrincipalName' => 'principal-user@example.com',
    ]);

    callbackWith(ssoHandshake())->assertRedirect(route('admin.dashboard.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});
