<?php

namespace Webkul\Admin\Sso;

use Illuminate\Support\Facades\Http;

class MicrosoftProvider extends AbstractOAuthProvider
{
    /**
     * Authority values that let accounts from any directory reach the callback,
     * so the tenant the user actually came from has to be checked explicitly.
     */
    private const MULTI_TENANT_AUTHORITIES = ['common', 'organizations', 'consumers'];

    public function getCode(): string
    {
        return 'microsoft';
    }

    public function getLabel(): string
    {
        return trans('admin::app.users.sessions.sso-sign-in-with-microsoft');
    }

    public function getIconView(): ?string
    {
        return 'admin::components.sso.icons.microsoft';
    }

    public function isEnabled(): bool
    {
        $config = $this->config();

        return $config['enabled']
            && $config['client_id'] !== ''
            && $config['client_secret'] !== ''
            && $config['tenant'] !== '';
    }

    protected function buildAuthorizationUrl(string $state, string $codeChallenge, string $nonce): string
    {
        $config = $this->config();

        $query = http_build_query([
            'client_id'             => $config['client_id'],
            'response_type'         => 'code',
            'redirect_uri'          => $this->getCallbackUrl(),
            'response_mode'         => 'query',
            'scope'                 => 'openid profile email User.Read',
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/authorize?{$query}";
    }

    protected function exchangeCodeForToken(string $authorizationCode, string $codeVerifier): ?SsoToken
    {
        $config = $this->config();

        $response = Http::asForm()->timeout(10)->post(
            "https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/token",
            [
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code'          => $authorizationCode,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $this->getCallbackUrl(),
                'code_verifier' => $codeVerifier,
            ]
        );

        if (! $response->successful()) {
            return null;
        }

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            return null;
        }

        $idToken = $response->json('id_token');

        return new SsoToken(
            accessToken: $accessToken,
            idToken: is_string($idToken) ? $idToken : null,
        );
    }

    /**
     * Rejects replayed tokens and, more importantly, accounts from a directory we
     * never intended to trust. Without this an operator switching the tenant to
     * "common" would silently let every Microsoft account on earth reach the
     * account lookup.
     */
    protected function isTokenAcceptable(SsoToken $token, string $nonce): bool
    {
        $claims = $this->decodeTokenClaims($token->idToken);

        if ($claims === []) {
            return false;
        }

        if (! is_string($claims['nonce'] ?? null) || ! hash_equals($nonce, $claims['nonce'])) {
            return false;
        }

        return $this->isTenantAllowed($claims['tid'] ?? null);
    }

    protected function fetchIdentity(SsoToken $token): ?SsoIdentity
    {
        $response = Http::withToken($token->accessToken)
            ->timeout(10)
            ->get('https://graph.microsoft.com/v1.0/me?$select=id,mail,userPrincipalName,displayName');

        if (! $response->successful()) {
            return null;
        }

        $identifier = $response->json('id');

        $email = $response->json('mail') ?: $response->json('userPrincipalName');

        if (
            ! is_string($identifier)
            || $identifier === ''
            || ! is_string($email)
            || $email === ''
        ) {
            return null;
        }

        $name = $response->json('displayName');

        return new SsoIdentity(
            identifier: $identifier,
            email: $email,
            name: is_string($name) ? $name : null,
            raw: $response->json() ?? [],
        );
    }

    /**
     * A pinned tenant id is enforced by Microsoft itself; the shared authorities are
     * not, so those require an explicit allow list and fail closed without one.
     */
    protected function isTenantAllowed(mixed $tenantId): bool
    {
        if (! is_string($tenantId) || $tenantId === '') {
            return false;
        }

        $configured = $this->config()['tenant'];

        if (! in_array(strtolower($configured), self::MULTI_TENANT_AUTHORITIES, true)) {
            return hash_equals(strtolower($configured), strtolower($tenantId));
        }

        $allowed = array_filter(array_map(
            'trim',
            explode(',', (string) config('services.microsoft_sso.allowed_tenants', ''))
        ));

        return in_array(strtolower($tenantId), array_map('strtolower', $allowed), true);
    }

    /**
     * Database configuration wins, environment values are the fallback.
     *
     * @return array{enabled: bool, tenant: string, client_id: string, client_secret: string}
     */
    protected function config(): array
    {
        $prefix = 'general.microsoft_sso.settings.';

        return [
            'enabled'       => (bool) (core()->getConfigData($prefix.'enabled') ?? config('services.microsoft_sso.enabled', false)),
            'tenant'        => (string) (core()->getConfigData($prefix.'tenant') ?: config('services.microsoft_sso.tenant', '')),
            'client_id'     => (string) (core()->getConfigData($prefix.'client_id') ?: config('services.microsoft_sso.client_id', '')),
            'client_secret' => (string) (core()->getConfigData($prefix.'client_secret') ?: config('services.microsoft_sso.client_secret', '')),
        ];
    }
}
