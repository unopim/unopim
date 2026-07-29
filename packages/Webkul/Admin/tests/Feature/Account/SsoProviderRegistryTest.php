<?php

use Illuminate\Support\Facades\Hash;
use Webkul\Admin\Sso\AbstractOAuthProvider;
use Webkul\Admin\Sso\SsoIdentity;
use Webkul\Admin\Sso\SsoManager;
use Webkul\Admin\Sso\SsoToken;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;

class FakeSsoProvider extends AbstractOAuthProvider
{
    public static bool $enabled = true;

    public static ?string $email = 'fake-sso-user@example.com';

    public function getCode(): string
    {
        return 'fake';
    }

    public function getLabel(): string
    {
        return 'Sign in with Fake';
    }

    public function isEnabled(): bool
    {
        return static::$enabled;
    }

    protected function buildAuthorizationUrl(string $state, string $codeChallenge, string $nonce): string
    {
        return 'https://fake-idp.test/authorize?state='.$state.'&code_challenge='.$codeChallenge;
    }

    protected function exchangeCodeForToken(string $authorizationCode, string $codeVerifier): ?SsoToken
    {
        return new SsoToken(accessToken: 'fake-access-token');
    }

    protected function fetchIdentity(SsoToken $token): ?SsoIdentity
    {
        return static::$email === null
            ? null
            : new SsoIdentity(identifier: 'fake-subject-'.static::$email, email: static::$email);
    }
}

beforeEach(function () {
    FakeSsoProvider::$enabled = true;
    FakeSsoProvider::$email = 'fake-sso-user@example.com';

    config()->set('sso.providers.fake', FakeSsoProvider::class);
    config()->set('services.microsoft_sso.enabled', false);

    app()->forgetInstance(SsoManager::class);
});

it('renders a sign-in button for a third-party registered driver', function () {
    get(route('admin.session.create'))
        ->assertOk()
        ->assertSee(route('admin.session.sso.redirect', ['provider' => 'fake']), false)
        ->assertSeeText('Sign in with Fake');
});

it('redirects to the driver authorization url and stores a namespaced state', function () {
    $response = get(route('admin.session.sso.redirect', ['provider' => 'fake']));

    $handshake = session('sso_handshake.fake');

    expect($handshake['state'])->toBeString()->not->toBeEmpty()
        ->and($handshake['verifier'])->toBeString()->not->toBeEmpty();

    $challenge = rtrim(strtr(base64_encode(hash('sha256', $handshake['verifier'], true)), '+/', '-_'), '=');

    $response->assertRedirect('https://fake-idp.test/authorize?state='.$handshake['state'].'&code_challenge='.$challenge);
});

it('logs an existing admin in through a third-party driver', function () {
    $admin = Admin::factory()->create([
        'email'    => 'fake-sso-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $this->withSession(['sso_handshake' => ['fake' => ['state' => 'valid-state', 'verifier' => 'v', 'nonce' => 'n']]])
        ->get(route('admin.session.sso.callback', [
            'provider' => 'fake',
            'state'    => 'valid-state',
            'code'     => 'auth-code',
        ]));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('rejects a callback whose state does not match the driver namespace', function () {
    Admin::factory()->create([
        'email'    => 'fake-sso-user@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
    ]);

    $response = $this->withSession(['sso_handshake' => ['microsoft' => ['state' => 'valid-state', 'verifier' => 'v', 'nonce' => 'n']]])
        ->get(route('admin.session.sso.callback', [
            'provider' => 'fake',
            'state'    => 'valid-state',
            'code'     => 'auth-code',
        ]));

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionHas('error');

    $this->assertGuest('admin');
});

it('hides a driver and refuses its routes once it reports itself disabled', function () {
    FakeSsoProvider::$enabled = false;

    get(route('admin.session.create'))
        ->assertOk()
        ->assertDontSeeText('Sign in with Fake');

    get(route('admin.session.sso.redirect', ['provider' => 'fake']))
        ->assertRedirect(route('admin.session.create'));
});

it('refuses an unregistered driver code', function () {
    get(route('admin.session.sso.redirect', ['provider' => 'nope']))
        ->assertRedirect(route('admin.session.create'));
});
