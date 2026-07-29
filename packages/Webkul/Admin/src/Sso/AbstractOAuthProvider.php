<?php

namespace Webkul\Admin\Sso;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Admin\Contracts\SsoProvider;

abstract class AbstractOAuthProvider implements SsoProvider
{
    public function getIconView(): ?string
    {
        return null;
    }

    public function getSort(): int
    {
        return 0;
    }

    public function getRedirectUrl(): string
    {
        return route('admin.session.sso.redirect', ['provider' => $this->getCode()]);
    }

    public function getCallbackUrl(): string
    {
        return route('admin.session.sso.callback', ['provider' => $this->getCode()]);
    }

    public function redirect(Request $request): RedirectResponse
    {
        $handshake = [
            'state'    => Str::random(40),
            'verifier' => Str::random(96),
            'nonce'    => Str::random(40),
        ];

        $request->session()->put($this->handshakeSessionKey(), $handshake);

        return redirect()->away($this->buildAuthorizationUrl(
            $handshake['state'],
            $this->codeChallengeFor($handshake['verifier']),
            $handshake['nonce'],
        ));
    }

    public function resolveIdentity(Request $request): ?SsoIdentity
    {
        $handshake = $request->session()->pull($this->handshakeSessionKey());

        $state = $request->query('state');

        $authorizationCode = $request->query('code');

        if (
            ! is_array($handshake)
            || ! is_string($handshake['state'] ?? null)
            || ! is_string($handshake['verifier'] ?? null)
            || ! is_string($handshake['nonce'] ?? null)
            || ! is_string($state)
            || ! is_string($authorizationCode)
            || ! hash_equals($handshake['state'], $state)
        ) {
            return null;
        }

        $token = $this->exchangeCodeForToken($authorizationCode, $handshake['verifier']);

        if (! $token || ! $this->isTokenAcceptable($token, $handshake['nonce'])) {
            return null;
        }

        return $this->fetchIdentity($token);
    }

    /**
     * Authorization endpoint the visitor is bounced to, carrying the CSRF state,
     * the PKCE challenge and the replay nonce.
     */
    abstract protected function buildAuthorizationUrl(string $state, string $codeChallenge, string $nonce): string;

    /**
     * Trade the authorization code for tokens, or null when the exchange fails.
     */
    abstract protected function exchangeCodeForToken(string $authorizationCode, string $codeVerifier): ?SsoToken;

    /**
     * Pull the identity from the provider's user endpoint.
     */
    abstract protected function fetchIdentity(SsoToken $token): ?SsoIdentity;

    /**
     * Provider-specific assertions on the returned tokens, such as issuer and nonce
     * checks. Defaults to accepting whatever the token endpoint returned.
     */
    protected function isTokenAcceptable(SsoToken $token, string $nonce): bool
    {
        return true;
    }

    /**
     * Decode a JWT payload without signature verification. Safe only for tokens
     * received straight from the token endpoint over TLS, per OIDC Core 3.1.3.7.
     *
     * @return array<string, mixed>
     */
    protected function decodeTokenClaims(?string $jwt): array
    {
        $segments = explode('.', (string) $jwt);

        if (count($segments) !== 3) {
            return [];
        }

        $payload = base64_decode(strtr($segments[1], '-_', '+/'), true);

        if ($payload === false) {
            return [];
        }

        $claims = json_decode($payload, true);

        return is_array($claims) ? $claims : [];
    }

    protected function codeChallengeFor(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Namespaced per driver so two active providers cannot consume each other's handshake.
     */
    protected function handshakeSessionKey(): string
    {
        return 'sso_handshake.'.$this->getCode();
    }
}
