<?php

use Firebase\JWT\JWT;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Webkul\Admin\Sso\OidcProvider;
use Webkul\Admin\Sso\OidcSettings;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function (): void {
    Http::preventStrayRequests();

    Cache::forget('unopim.sso.oidc.discovery.'.hash('sha256', oidcIssuer()));
    Cache::forget('unopim.sso.oidc.jwks.'.hash('sha256', oidcIssuer().'|'.oidcIdpUrl('jwks/')));

    oidcConfigure();
});

function oidcIssuer(): string
{
    return 'https://idp.example.test/application/o/unopim/';
}

function oidcIdpUrl(string $path): string
{
    return 'https://idp.example.test/application/o/'.$path;
}

/**
 * Environment-backed settings, as `UNOPIM_OIDC_*` variables map onto `services.oidc_sso.*`.
 */
function oidcConfigure(array $overrides = []): void
{
    $settings = array_merge([
        'enabled'                => true,
        'issuer'                 => oidcIssuer(),
        'client_id'              => 'unopim-client',
        'client_secret'          => 'unopim-secret',
        'scopes'                 => null,
        'label'                  => null,
        'require_verified_email' => null,
        'auto_provision'         => null,
        'default_role'           => null,
        'groups_claim'           => null,
        'role_map'               => null,
        'rp_logout'              => null,
        'sso_only'               => null,
    ], $overrides);

    foreach ($settings as $name => $value) {
        config()->set('services.oidc_sso.'.$name, $value);
    }
}

/**
 * Key pair per kid, generated once per run; kids starting with `ec-` are P-256, all others RSA.
 *
 * @return array{private: string, jwk: array<string, string>}
 */
function oidcKey(string $kid): array
{
    static $keys = [];

    if (isset($keys[$kid])) {
        return $keys[$kid];
    }

    $type = str_starts_with($kid, 'ec-') ? 'EC' : 'RSA';

    $resource = $type === 'EC'
        ? openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'])
        : openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);

    openssl_pkey_export($resource, $private);

    $details = openssl_pkey_get_details($resource);

    $jwk = $type === 'EC'
        ? ['kty' => 'EC', 'crv' => 'P-256', 'x' => JWT::urlsafeB64Encode($details['ec']['x']), 'y' => JWT::urlsafeB64Encode($details['ec']['y'])]
        : ['kty' => 'RSA', 'n' => JWT::urlsafeB64Encode($details['rsa']['n']), 'e' => JWT::urlsafeB64Encode($details['rsa']['e'])];

    return $keys[$kid] = ['private' => $private, 'jwk' => [...$jwk, 'kid' => $kid, 'use' => 'sig']];
}

/**
 * @return array<string, mixed>
 */
function oidcJwks(string ...$kids): array
{
    return ['keys' => array_map(fn (string $kid): array => oidcKey($kid)['jwk'], $kids)];
}

function oidcIdToken(array $claims = [], string $kid = 'rsa-1', string $algorithm = 'RS256', ?string $signingKey = null): string
{
    $payload = array_merge([
        'iss'            => oidcIssuer(),
        'aud'            => 'unopim-client',
        'sub'            => 'subject-1',
        'nonce'          => 'valid-nonce',
        'email'          => 'oidc-user@example.com',
        'email_verified' => true,
        'name'           => 'OIDC User',
        'iat'            => time(),
        'exp'            => time() + 300,
    ], $claims);

    return JWT::encode($payload, $signingKey ?? oidcKey($kid)['private'], $algorithm, $kid);
}

function oidcUnsignedToken(array $claims = []): string
{
    $segment = fn (array $part): string => JWT::urlsafeB64Encode(json_encode($part));

    return $segment(['alg' => 'none', 'typ' => 'JWT', 'kid' => 'rsa-1']).'.'.$segment(array_merge([
        'iss'   => oidcIssuer(),
        'aud'   => 'unopim-client',
        'sub'   => 'subject-1',
        'nonce' => 'valid-nonce',
        'email' => 'oidc-user@example.com',
        'iat'   => time(),
        'exp'   => time() + 300,
    ], $claims)).'.';
}

/**
 * @return array<string, mixed>
 */
function oidcDiscovery(array $overrides = []): array
{
    return array_merge([
        'issuer'                                => oidcIssuer(),
        'authorization_endpoint'                => oidcIdpUrl('authorize/'),
        'token_endpoint'                        => oidcIdpUrl('token/'),
        'userinfo_endpoint'                     => oidcIdpUrl('userinfo/'),
        'jwks_uri'                              => oidcIdpUrl('jwks/'),
        'end_session_endpoint'                  => oidcIdpUrl('unopim/end-session/'),
        'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
    ], $overrides);
}

function oidcFakeIdp(?string $idToken = null, array $options = []): void
{
    Http::fake([
        oidcIssuer().'.well-known/openid-configuration' => Http::response(oidcDiscovery($options['discovery'] ?? [])),
        oidcIdpUrl('token/')                            => Http::response(['access_token' => 'access-token', 'token_type' => 'Bearer', 'id_token' => $idToken ?? oidcIdToken()]),
        oidcIdpUrl('jwks/')                             => $options['jwks'] ?? Http::response(oidcJwks('rsa-1', 'ec-1')),
        oidcIdpUrl('userinfo/')                         => Http::response($options['userinfo'] ?? ['sub' => 'subject-1']),
    ]);
}

function oidcCallback(): TestResponse
{
    return test()->withSession(['sso_handshake' => ['oidc' => [
        'state'    => 'valid-state',
        'verifier' => 'valid-verifier',
        'nonce'    => 'valid-nonce',
    ]]])->get(route('admin.session.sso.callback', [
        'provider' => 'oidc',
        'state'    => 'valid-state',
        'code'     => 'auth-code',
    ]));
}

function oidcAdmin(array $attributes = []): Admin
{
    return Admin::factory()->create(array_merge([
        'email'    => 'oidc-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ], $attributes));
}

function oidcRole(string $permissionType = 'custom'): Role
{
    return Role::factory()->create([
        'name'            => 'OIDC role '.uniqid(),
        'permission_type' => $permissionType,
        'permissions'     => $permissionType === 'custom' ? ['dashboard'] : [],
    ]);
}

function oidcRecorded(string $url): int
{
    return Http::recorded(fn (HttpRequest $request): bool => str_starts_with($request->url(), $url))->count();
}

function oidcAssertRejected(TestResponse $response): void
{
    $response->assertRedirect(route('admin.session.create'));

    test()->assertGuest('admin');
}

it('logs in an admin already linked by subject id', function (): void {
    $admin = oidcAdmin(['email' => 'renamed@example.com', 'sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    oidcFakeIdp();

    oidcCallback()->assertRedirect(route('admin.dashboard.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('links an existing admin by verified email on first sign in', function (): void {
    $admin = oidcAdmin();

    oidcFakeIdp();

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');

    expect($admin->fresh()->sso_provider)->toBe('oidc')
        ->and($admin->fresh()->sso_identifier)->toBe('subject-1');
});

it('sends the code with the pkce verifier and the client secret in the body', function (): void {
    oidcAdmin();

    oidcFakeIdp();

    oidcCallback();

    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === oidcIdpUrl('token/')
        && $request['code'] === 'auth-code'
        && $request['code_verifier'] === 'valid-verifier'
        && $request['client_secret'] === 'unopim-secret'
        && ! $request->hasHeader('Authorization'));
});

it('falls back to client_secret_basic when the provider does not advertise client_secret_post', function (): void {
    oidcAdmin();

    oidcFakeIdp(options: ['discovery' => ['token_endpoint_auth_methods_supported' => ['client_secret_basic']]]);

    oidcCallback();

    $this->assertAuthenticated('admin');

    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === oidcIdpUrl('token/')
        && ! isset($request['client_secret'])
        && $request->header('Authorization')[0] === 'Basic '.base64_encode('unopim-client:unopim-secret'));
});

it('caches discovery and signing keys across logins', function (): void {
    oidcAdmin();

    oidcFakeIdp();

    oidcCallback();

    auth()->guard('admin')->logout();

    oidcCallback();

    $this->assertAuthenticated('admin');

    expect(oidcRecorded(oidcIssuer().'.well-known/openid-configuration'))->toBe(1)
        ->and(oidcRecorded(oidcIdpUrl('jwks/')))->toBe(1)
        ->and(oidcRecorded(oidcIdpUrl('token/')))->toBe(2);
});

it('builds the authorization request from discovery with pkce, nonce and openid scope', function (): void {
    oidcConfigure(['scopes' => 'profile, email groups']);

    oidcFakeIdp();

    $response = $this->get(route('admin.session.sso.redirect', ['provider' => 'oidc']));

    $handshake = session('sso_handshake.oidc');

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($response->headers->get('Location'))->toStartWith(oidcIdpUrl('authorize/').'?')
        ->and($query['scope'])->toBe('openid profile email groups')
        ->and($query['client_id'])->toBe('unopim-client')
        ->and($query['state'])->toBe($handshake['state'])
        ->and($query['nonce'])->toBe($handshake['nonce'])
        ->and($query['code_challenge_method'])->toBe('S256')
        ->and($query['redirect_uri'])->toBe(route('admin.session.sso.callback', ['provider' => 'oidc']));
});

it('signs in straight from the identity provider app tile without visiting the login page', function (): void {
    $admin = oidcAdmin(['sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    $nonce = null;

    Http::fake([
        oidcIssuer().'.well-known/openid-configuration' => Http::response(oidcDiscovery()),
        oidcIdpUrl('token/')                            => function () use (&$nonce): PromiseInterface {
            return Http::response(['access_token' => 'access-token', 'token_type' => 'Bearer', 'id_token' => oidcIdToken(['nonce' => $nonce])]);
        },
        oidcIdpUrl('jwks/')                             => Http::response(oidcJwks('rsa-1')),
    ]);

    $location = $this->get(route('admin.session.sso.redirect', ['provider' => 'oidc']))
        ->assertRedirect()
        ->headers->get('Location');

    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    $nonce = $query['nonce'];

    $this->get(route('admin.session.sso.callback', [
        'provider' => 'oidc',
        'state'    => $query['state'],
        'code'     => 'auth-code',
    ]))->assertRedirect(route('admin.dashboard.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('sends an admin who is already signed in straight to the landing page', function (): void {
    $this->loginAsAdmin(oidcAdmin(['use_gravatar' => false]));

    oidcFakeIdp();

    $this->get(route('admin.session.sso.redirect', ['provider' => 'oidc']))
        ->assertRedirect(route('admin.dashboard.index'));

    expect(session('sso_handshake.oidc'))->toBeNull();

    Http::assertNothingSent();
});

it('refuses an issuer that does not use https', function (): void {
    oidcConfigure(['issuer' => 'http://idp.example.test/application/o/unopim/']);

    oidcFakeIdp();

    $this->get(route('admin.session.sso.redirect', ['provider' => 'oidc']))
        ->assertRedirect(route('admin.session.create'));

    Http::assertNothingSent();
});

it('refuses a discovery document naming a different issuer', function (): void {
    oidcAdmin();

    oidcFakeIdp(options: ['discovery' => ['issuer' => 'https://evil.example.test/']]);

    oidcAssertRejected(oidcCallback());
});

it('accepts an ES256 signed id token', function (): void {
    $admin = oidcAdmin();

    oidcFakeIdp(oidcIdToken(kid: 'ec-1', algorithm: 'ES256'));

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('refuses an id token signed by a key the provider does not publish', function (): void {
    oidcAdmin();

    $foreign = oidcKey('foreign-key')['private'];

    oidcFakeIdp(oidcIdToken(signingKey: $foreign));

    oidcAssertRejected(oidcCallback());
});

it('refuses an id token whose claims do not check out', function (array $claims): void {
    oidcAdmin();

    oidcFakeIdp(oidcIdToken($claims));

    oidcAssertRejected(oidcCallback());
})->with([
    'wrong issuer'                    => [['iss' => 'https://evil.example.test/application/o/unopim/']],
    'issuer without trailing slash'   => [['iss' => rtrim('https://idp.example.test/application/o/unopim/', '/')]],
    'wrong audience'                  => [['aud' => 'another-client']],
    'several audiences without azp'   => [['aud' => ['unopim-client', 'another-client']]],
    'authorized party of another app' => [['aud' => ['unopim-client', 'another-client'], 'azp' => 'another-client']],
    'wrong nonce'                     => [['nonce' => 'replayed-nonce']],
    'expired'                         => [['exp' => time() - 120, 'iat' => time() - 600]],
    'not yet valid'                   => [['nbf' => time() + 600]],
    'missing subject'                 => [['sub' => '']],
]);

it('tolerates small clock skew and several audiences with a matching azp', function (): void {
    $admin = oidcAdmin();

    oidcFakeIdp(oidcIdToken([
        'exp' => time() - 30,
        'aud' => ['unopim-client', 'another-client'],
        'azp' => 'unopim-client',
    ]));

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('refuses an unsigned id token', function (): void {
    oidcAdmin();

    oidcFakeIdp(oidcUnsignedToken());

    oidcAssertRejected(oidcCallback());
});

it('refuses an HS256 id token signed with the client secret', function (): void {
    oidcAdmin();

    oidcConfigure(['client_secret' => str_repeat('unopim-secret-', 4)]);

    oidcFakeIdp(oidcIdToken(algorithm: 'HS256', signingKey: str_repeat('unopim-secret-', 4)));

    oidcAssertRejected(oidcCallback());
});

it('refetches the key set once for an unknown kid after key rotation', function (): void {
    $admin = oidcAdmin();

    oidcFakeIdp(oidcIdToken(kid: 'rsa-2'), [
        'jwks' => Http::sequence()
            ->push(oidcJwks('rsa-1'))
            ->push(oidcJwks('rsa-1', 'rsa-2')),
    ]);

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');

    expect(oidcRecorded(oidcIdpUrl('jwks/')))->toBe(2);
});

it('refuses an unknown kid that is still unknown after refetching', function (): void {
    oidcAdmin();

    oidcFakeIdp(oidcIdToken(kid: 'rsa-3'), ['jwks' => Http::response(oidcJwks('rsa-1'))]);

    oidcAssertRejected(oidcCallback());

    expect(oidcRecorded(oidcIdpUrl('jwks/')))->toBe(2);
});

it('reads the email from userinfo when the id token has none', function (): void {
    $admin = oidcAdmin();

    oidcFakeIdp(oidcIdToken(['email' => null, 'email_verified' => null]), [
        'userinfo' => ['sub' => 'subject-1', 'email' => 'oidc-user@example.com', 'email_verified' => true],
    ]);

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');

    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === oidcIdpUrl('userinfo/')
        && $request->header('Authorization')[0] === 'Bearer access-token');
});

it('never pairs an email from one document with the verification flag of the other', function (array $tokenClaims, array $userInfo): void {
    $admin = oidcAdmin();

    oidcConfigure(['require_verified_email' => false]);

    oidcFakeIdp(oidcIdToken($tokenClaims), ['userinfo' => array_merge(['sub' => 'subject-1'], $userInfo)]);

    oidcAssertRejected(oidcCallback());

    expect($admin->fresh()->sso_identifier)->toBeNull();
})->with([
    'email in the id token, flag only in userinfo' => [
        ['email' => 'oidc-user@example.com', 'email_verified' => null],
        ['email' => 'someone-else@example.com', 'email_verified' => true],
    ],
    'flag in the id token, email only in userinfo' => [
        ['email' => null, 'email_verified' => true],
        ['email' => 'oidc-user@example.com', 'email_verified' => false],
    ],
]);

it('refuses userinfo that describes a different subject', function (): void {
    oidcAdmin();

    oidcFakeIdp(oidcIdToken(['email' => null, 'email_verified' => null]), [
        'userinfo' => ['sub' => 'someone-else', 'email' => 'oidc-user@example.com', 'email_verified' => true],
    ]);

    oidcAssertRejected(oidcCallback());
});

it('refuses an unverified email by default', function (): void {
    oidcAdmin();

    oidcFakeIdp(oidcIdToken(['email_verified' => false]));

    oidcAssertRejected(oidcCallback());
});

it('never links an existing admin by an unverified email', function (): void {
    $admin = oidcAdmin();

    oidcConfigure(['require_verified_email' => false]);

    oidcFakeIdp(oidcIdToken(['email_verified' => false]));

    oidcAssertRejected(oidcCallback());

    expect($admin->fresh()->sso_provider)->toBeNull()
        ->and($admin->fresh()->sso_identifier)->toBeNull();
});

it('lets an admin already linked by subject id in with an unverified email once the switch is off', function (): void {
    $admin = oidcAdmin(['sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    oidcConfigure(['require_verified_email' => false]);

    oidcFakeIdp(oidcIdToken(['email_verified' => false]));

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('provisions an unverified email once the switch is off', function (): void {
    $role = oidcRole();

    oidcConfigure(['require_verified_email' => false, 'auto_provision' => true, 'default_role' => $role->name]);

    oidcFakeIdp(oidcIdToken(['email' => 'new-unverified@example.com', 'email_verified' => false]));

    oidcCallback();

    $admin = Admin::query()->where('email', 'new-unverified@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role_id)->toBe($role->id);

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('refuses to hijack an admin linked to a different subject through its email', function (): void {
    $admin = oidcAdmin(['sso_provider' => 'oidc', 'sso_identifier' => 'original-subject']);

    oidcFakeIdp();

    oidcAssertRejected(oidcCallback());

    expect($admin->fresh()->sso_identifier)->toBe('original-subject');
});

it('refuses to re-bind an admin linked through another sso driver', function (): void {
    $admin = oidcAdmin(['sso_provider' => 'microsoft', 'sso_identifier' => 'object-id-1']);

    oidcFakeIdp();

    oidcAssertRejected(oidcCallback());

    expect($admin->fresh()->sso_provider)->toBe('microsoft')
        ->and($admin->fresh()->sso_identifier)->toBe('object-id-1');
});

it('never links or provisions over an admin whose email only collates with the asserted one', function (bool $provisions): void {
    $admin = oidcAdmin(['email' => 'jose@corp.com']);

    oidcConfigure(['auto_provision' => $provisions, 'default_role' => oidcRole()->name]);

    oidcFakeIdp(oidcIdToken(['email' => 'jose@cörp.com']));

    oidcCallback();

    expect($admin->fresh()->sso_identifier)->toBeNull()
        ->and(auth()->guard('admin')->id())->not->toBe($admin->id);
})->with([
    'provisioning off' => false,
    'provisioning on'  => true,
]);

it('never treats a subject that differs only in case as the linked one', function (): void {
    $admin = oidcAdmin(['sso_provider' => 'oidc', 'sso_identifier' => 'SUBJECT-1']);

    oidcFakeIdp();

    oidcAssertRejected(oidcCallback());

    expect($admin->fresh()->sso_identifier)->toBe('SUBJECT-1');
});

it('refuses an unknown user while provisioning is off', function (): void {
    oidcFakeIdp(oidcIdToken(['email' => 'stranger@example.com']));

    oidcAssertRejected(oidcCallback());

    $this->assertDatabaseMissing('admins', ['email' => 'stranger@example.com']);
});

it('refuses provisioning without a role mapping or default role', function (): void {
    oidcConfigure(['auto_provision' => true]);

    oidcFakeIdp(oidcIdToken(['email' => 'roleless@example.com']));

    oidcAssertRejected(oidcCallback());

    $this->assertDatabaseMissing('admins', ['email' => 'roleless@example.com']);
});

it('provisions a new admin with the mapped role', function (): void {
    $editor = oidcRole();

    $viewer = oidcRole();

    oidcConfigure([
        'auto_provision' => true,
        'role_map'       => json_encode(['pim-viewers' => $viewer->name, 'pim-editors' => $editor->name]),
    ]);

    oidcFakeIdp(oidcIdToken([
        'sub'    => 'new-subject',
        'email'  => 'Provisioned@Example.com',
        'name'   => 'Provisioned Person',
        'groups' => ['pim-editors', 'pim-viewers'],
    ]));

    oidcCallback()->assertRedirect();

    $admin = Admin::query()->where('sso_identifier', 'new-subject')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->email)->toBe('Provisioned@Example.com')
        ->and($admin->name)->toBe('Provisioned Person')
        ->and($admin->sso_provider)->toBe('oidc')
        ->and($admin->role_id)->toBe($viewer->id)
        ->and((int) $admin->status)->toBe(1)
        ->and($admin->type)->toBe('user')
        ->and($admin->ui_locale_id)->not->toBeNull()
        ->and($admin->timezone)->not->toBeEmpty()
        ->and(Hash::check('', $admin->password))->toBeFalse();

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('fires the admin create events for a provisioned admin', function (): void {
    $fired = [];

    Event::listen('user.admin.create.before', function () use (&$fired): void {
        $fired[] = 'before';
    });

    Event::listen('user.admin.create.after', function (Admin $admin) use (&$fired): void {
        $fired[] = 'after:'.$admin->sso_identifier;
    });

    oidcConfigure(['auto_provision' => true, 'default_role' => oidcRole()->name]);

    oidcFakeIdp(oidcIdToken(['sub' => 'evented-subject', 'email' => 'evented@example.com']));

    oidcCallback()->assertRedirect();

    expect($fired)->toBe(['before', 'after:evented-subject']);
});

it('syncs the mapped role on every login', function (): void {
    $previous = oidcRole();

    $mapped = oidcRole();

    $admin = oidcAdmin(['role_id' => $previous->id, 'sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    oidcConfigure(['role_map' => json_encode(['pim-editors' => $mapped->name])]);

    oidcFakeIdp(oidcIdToken(['groups' => ['pim-editors']]));

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');

    expect($admin->fresh()->role_id)->toBe($mapped->id);
});

it('reads the groups from a custom claim', function (): void {
    $mapped = oidcRole();

    $admin = oidcAdmin(['role_id' => oidcRole()->id]);

    oidcConfigure(['role_map' => json_encode(['editors' => $mapped->name]), 'groups_claim' => 'roles']);

    oidcFakeIdp(oidcIdToken(['roles' => 'editors']));

    oidcCallback();

    expect($admin->fresh()->role_id)->toBe($mapped->id);
});

it('refuses a login whose groups match no mapped role', function (): void {
    $role = oidcRole();

    $admin = oidcAdmin(['role_id' => $role->id]);

    oidcConfigure(['auto_provision' => true, 'role_map' => json_encode(['pim-editors' => oidcRole()->name])]);

    oidcFakeIdp(oidcIdToken(['groups' => ['marketing']]));

    oidcAssertRejected(oidcCallback());

    expect($admin->fresh()->role_id)->toBe($role->id);
});

it('refuses every login while the role map is not valid json', function (): void {
    oidcAdmin();

    oidcConfigure(['role_map' => '{not json']);

    oidcFakeIdp(oidcIdToken(['groups' => ['pim-editors']]));

    oidcAssertRejected(oidcCallback());
});

it('keeps the last active full-access admin from being demoted by the role map', function (): void {
    $fullAccess = oidcRole('all');

    $admin = oidcAdmin(['role_id' => $fullAccess->id, 'sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    Admin::query()
        ->whereKeyNot($admin->id)
        ->whereIn('role_id', Role::query()->where('permission_type', 'all')->select('id'))
        ->update(['status' => 0]);

    oidcConfigure(['role_map' => json_encode(['pim-editors' => oidcRole()->name])]);

    oidcFakeIdp(oidcIdToken(['groups' => ['pim-editors']]));

    oidcCallback();

    $this->assertAuthenticatedAs($admin, 'admin');

    expect($admin->fresh()->role_id)->toBe($fullAccess->id);
});

it('demotes a full-access admin when another active full-access admin remains', function (): void {
    $fullAccess = oidcRole('all');

    $mapped = oidcRole();

    oidcAdmin(['email' => 'other-admin@example.com', 'role_id' => $fullAccess->id]);

    $admin = oidcAdmin(['role_id' => $fullAccess->id, 'sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    oidcConfigure(['role_map' => json_encode(['pim-editors' => $mapped->name])]);

    oidcFakeIdp(oidcIdToken(['groups' => ['pim-editors']]));

    oidcCallback();

    expect($admin->fresh()->role_id)->toBe($mapped->id);
});

it('refuses an inactive admin', function (): void {
    oidcAdmin(['status' => 0]);

    oidcFakeIdp();

    $response = oidcCallback();

    oidcAssertRejected($response);

    $response->assertSessionHas('warning');
});

it('refuses an api user and leaves its role alone', function (): void {
    $role = oidcRole();

    $apiUser = oidcAdmin(['type' => 'api', 'role_id' => $role->id, 'sso_provider' => 'oidc', 'sso_identifier' => 'subject-1']);

    oidcConfigure(['role_map' => json_encode(['pim-editors' => oidcRole()->name])]);

    oidcFakeIdp(oidcIdToken(['groups' => ['pim-editors']]));

    oidcAssertRejected(oidcCallback());

    expect($apiUser->fresh()->role_id)->toBe($role->id);
});

it('prefers environment values over stored settings', function (): void {
    oidcConfigure(['issuer' => null, 'client_id' => null]);

    app(CoreConfigRepository::class)->create([
        'general' => ['oidc_sso' => ['settings' => [
            'issuer'    => 'https://stored.example.test/',
            'client_id' => 'stored-client',
            'label'     => 'Stored label',
        ]]],
    ]);

    oidcConfigure(['label' => 'Sign in with ACME Login']);

    $settings = app(OidcSettings::class);

    expect($settings->issuer())->toBe(oidcIssuer())
        ->and($settings->clientId())->toBe('unopim-client')
        ->and($settings->label())->toBe('Sign in with ACME Login');

    oidcConfigure(['issuer' => null, 'client_id' => null]);

    expect($settings->issuer())->toBe('https://stored.example.test/')
        ->and($settings->clientId())->toBe('stored-client');
});

it('shows the configured label on the login page', function (): void {
    oidcConfigure(['label' => 'Sign in with ACME Login']);

    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee(route('admin.session.sso.redirect', ['provider' => 'oidc']), false)
        ->assertSeeText('Sign in with ACME Login');
});

it('falls back to the translated default label and hides the button while disabled', function (): void {
    $this->get(route('admin.session.create'))
        ->assertSeeText(trans('admin::app.users.sessions.sso-sign-in-with-oidc'));

    oidcConfigure(['client_secret' => null]);

    $this->get(route('admin.session.create'))
        ->assertDontSee(route('admin.session.sso.redirect', ['provider' => 'oidc']), false);
});

it('renders environment-locked fields read-only and never prints the secret', function (): void {
    $this->loginAsAdmin(oidcAdmin(['email' => 'settings-admin@example.com', 'use_gravatar' => false]));

    $html = $this->get(route('admin.settings.system.edit', 'system.oidc_sso'))
        ->assertOk()
        ->assertSee(trans('admin::app.configuration.index.env-locked'))
        ->getContent();

    expect($html)->toContain(e(oidcIssuer()))
        ->and($html)->toContain('&quot;locked&quot;:true')
        ->and($html)->not->toContain('unopim-secret');
});

it('reserves the openid connect settings for full-access admins', function (): void {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['configuration', 'configuration.system_settings', 'configuration.system_settings.oidc_sso'],
    ]);

    $this->loginAsAdmin(oidcAdmin(['email' => 'delegated@example.com', 'role_id' => $role->id, 'use_gravatar' => false]));

    $payload = ['general' => ['oidc_sso' => ['settings' => ['issuer' => 'https://attacker.example.test/']]]];

    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertDontSee(trans('admin::app.settings.system-settings.oidc-sso.title'));

    $this->get(route('admin.settings.system.edit', 'system.oidc_sso'))->assertForbidden();

    $this->put(route('admin.settings.system.update', 'system.oidc_sso'), $payload)->assertForbidden();

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'oidc_sso']), $payload)->assertForbidden();

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.oidc_sso.settings.issuer',
        'value' => 'https://attacker.example.test/',
    ]);
});

it('stores the client secret encrypted and never prints it on the settings page', function (): void {
    oidcConfigure(['client_secret' => null]);

    app(CoreConfigRepository::class)->create([
        'general' => ['oidc_sso' => ['settings' => ['client_secret' => 'stored-client-secret']]],
    ]);

    expect(CoreConfig::query()->where('code', 'general.oidc_sso.settings.client_secret')->value('value'))
        ->not->toBe('stored-client-secret')
        ->and(app(OidcSettings::class)->clientSecret())->toBe('stored-client-secret');

    $this->loginAsAdmin(oidcAdmin(['email' => 'settings-admin@example.com', 'use_gravatar' => false]));

    $this->get(route('admin.settings.system.edit', 'system.oidc_sso'))
        ->assertOk()
        ->assertDontSee('stored-client-secret');
});

it('ignores environment-locked fields on save', function (): void {
    $this->loginAsAdmin(oidcAdmin(['email' => 'settings-admin@example.com', 'use_gravatar' => false]));

    oidcConfigure(['client_id' => null]);

    $this->put(route('admin.settings.system.update', 'system.oidc_sso'), [
        'general' => ['oidc_sso' => ['settings' => [
            'issuer'    => 'https://attacker.example.test/',
            'client_id' => 'saved-client',
        ]]],
    ])->assertRedirect(route('admin.settings.system.edit', 'system.oidc_sso'));

    $this->assertDatabaseMissing('core_config', ['code' => 'general.oidc_sso.settings.issuer']);

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.oidc_sso.settings.client_id',
        'value' => 'saved-client',
    ]);
});

it('hides password sign-in and refuses password posts in sso-only mode', function (): void {
    oidcConfigure(['sso_only' => true]);

    oidcAdmin();

    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertDontSee('name="password"', false)
        ->assertDontSee(route('admin.forget_password.create'), false)
        ->assertSee(route('admin.session.sso.redirect', ['provider' => 'oidc']), false);

    $this->postJson(route('admin.session.store'), ['email' => 'oidc-user@example.com', 'password' => 'password'])
        ->assertForbidden();

    $this->assertGuest('admin');

    $this->get(route('admin.forget_password.create'))->assertRedirect(route('admin.session.create'));

    $this->post(route('admin.forget_password.store'), ['email' => 'oidc-user@example.com'])
        ->assertRedirect(route('admin.session.create'));

    $this->get(route('admin.reset_password.create', ['token' => 'any-token']))
        ->assertRedirect(route('admin.session.create'));
});

it('keeps password sign-in while sso-only is set but the provider is not usable', function (): void {
    oidcConfigure(['sso_only' => true, 'enabled' => false]);

    oidcAdmin();

    $this->get(route('admin.session.create'))->assertSee('name="password"', false);

    $this->post(route('admin.session.store'), ['email' => 'oidc-user@example.com', 'password' => 'password']);

    $this->assertAuthenticated('admin');
});

it('stores the id token in the session after an oidc login', function (): void {
    oidcAdmin();

    $idToken = oidcIdToken();

    oidcFakeIdp($idToken);

    oidcCallback()->assertSessionHas(OidcProvider::ID_TOKEN_SESSION_KEY, $idToken);
});

it('ends the provider session on logout of an oidc session', function (): void {
    $admin = oidcAdmin();

    oidcFakeIdp();

    $response = $this->actingAs($admin, 'admin')
        ->withSession([OidcProvider::ID_TOKEN_SESSION_KEY => 'raw-id-token'])
        ->delete(route('admin.session.destroy'));

    $this->assertGuest('admin');

    $location = $response->assertRedirect()->headers->get('Location');

    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith(oidcIdpUrl('unopim/end-session/').'?')
        ->and($query)->toBe([
            'id_token_hint'            => 'raw-id-token',
            'post_logout_redirect_uri' => route('admin.session.create'),
            'client_id'                => 'unopim-client',
        ]);
});

it('logs out locally only when rp logout is off or the session did not use oidc', function (array $session, array $settings): void {
    $admin = oidcAdmin();

    oidcConfigure($settings);

    oidcFakeIdp();

    $this->actingAs($admin, 'admin')
        ->withSession($session)
        ->delete(route('admin.session.destroy'))
        ->assertRedirect(route('admin.session.create'));

    $this->assertGuest('admin');
})->with([
    'rp logout off'     => [[OidcProvider::ID_TOKEN_SESSION_KEY => 'raw-id-token'], ['rp_logout' => false]],
    'password session'  => [[], []],
]);
