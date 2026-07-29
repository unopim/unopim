<?php

namespace Webkul\Admin\Sso;

use Illuminate\Support\Facades\Http;

class MicrosoftProvider extends AbstractOAuthProvider
{
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

    protected function buildAuthorizationUrl(string $state): string
    {
        $config = $this->config();

        $query = http_build_query([
            'client_id'     => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri'  => $this->getCallbackUrl(),
            'response_mode' => 'query',
            'scope'         => 'openid profile email User.Read',
            'state'         => $state,
        ]);

        return "https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/authorize?{$query}";
    }

    protected function exchangeCodeForToken(string $authorizationCode): ?string
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
            ]
        );

        if (! $response->successful()) {
            return null;
        }

        $accessToken = $response->json('access_token');

        return is_string($accessToken) && $accessToken !== '' ? $accessToken : null;
    }

    protected function fetchIdentity(string $accessToken): ?SsoIdentity
    {
        $response = Http::withToken($accessToken)
            ->timeout(10)
            ->get('https://graph.microsoft.com/v1.0/me?$select=mail,userPrincipalName,displayName');

        if (! $response->successful()) {
            return null;
        }

        $email = $response->json('mail') ?: $response->json('userPrincipalName');

        if (! is_string($email) || $email === '') {
            return null;
        }

        $name = $response->json('displayName');

        return new SsoIdentity(
            email: $email,
            name: is_string($name) ? $name : null,
            raw: $response->json() ?? [],
        );
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
