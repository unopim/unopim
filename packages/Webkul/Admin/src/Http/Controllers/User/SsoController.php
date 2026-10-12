<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Contracts\SsoProvider;
use Webkul\Admin\Exceptions\SsoAuthenticationException;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Sso\SsoAuthenticator;
use Webkul\Admin\Sso\SsoManager;
use Webkul\Admin\Traits\ResolvesLandingUrl;

class SsoController extends Controller
{
    use ResolvesLandingUrl;

    public function __construct(
        protected readonly SsoManager $ssoManager,
        protected readonly SsoAuthenticator $authenticator,
    ) {}

    /**
     * Hand the visitor off to the identity provider.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $driver = $this->resolveProvider($provider);

        if (! $driver) {
            return redirect()->route('admin.session.create');
        }

        return $driver->redirect($request);
    }

    /**
     * Handle the identity provider callback and open an admin session.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $driver = $this->resolveProvider($provider);

        if (! $driver) {
            return redirect()->route('admin.session.create');
        }

        $identity = $driver->resolveIdentity($request);

        if (! $identity) {
            session()->flash('error', trans('admin::app.settings.users.login-error'));

            return redirect()->route('admin.session.create');
        }

        try {
            $this->authenticator->authenticate($identity, $driver, $request);
        } catch (SsoAuthenticationException $exception) {
            session()->flash($exception->flashType, $exception->getMessage());

            return redirect()->route('admin.session.create')->withInput(['email' => $exception->email]);
        }

        return redirect()->intended($this->firstAllowedUrl());
    }

    protected function resolveProvider(string $provider): ?SsoProvider
    {
        return $this->ssoManager->getEnabled($provider);
    }
}
