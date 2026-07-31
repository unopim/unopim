<?php

namespace Webkul\Admin\Tests\Support;

use Webkul\Admin\Sso\AbstractOAuthProvider;
use Webkul\Admin\Sso\SsoIdentity;
use Webkul\Admin\Sso\SsoToken;

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
