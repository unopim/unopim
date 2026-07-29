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
        $state = Str::random(40);

        $request->session()->put($this->stateSessionKey(), $state);

        return redirect()->away($this->buildAuthorizationUrl($state));
    }

    public function resolveIdentity(Request $request): ?SsoIdentity
    {
        $expectedState = $request->session()->pull($this->stateSessionKey());

        $state = $request->query('state');

        $authorizationCode = $request->query('code');

        if (
            ! is_string($expectedState)
            || ! is_string($state)
            || ! is_string($authorizationCode)
            || ! hash_equals($expectedState, $state)
        ) {
            return null;
        }

        $accessToken = $this->exchangeCodeForToken($authorizationCode);

        return $accessToken === null ? null : $this->fetchIdentity($accessToken);
    }

    /**
     * Authorization endpoint the visitor is bounced to, with the CSRF state embedded.
     */
    abstract protected function buildAuthorizationUrl(string $state): string;

    /**
     * Trade the authorization code for an access token, or null when the exchange fails.
     */
    abstract protected function exchangeCodeForToken(string $authorizationCode): ?string;

    /**
     * Pull the identity from the provider's user endpoint.
     */
    abstract protected function fetchIdentity(string $accessToken): ?SsoIdentity;

    /**
     * Namespaced per driver so two active providers cannot consume each other's state.
     */
    protected function stateSessionKey(): string
    {
        return 'sso_state.'.$this->getCode();
    }
}
