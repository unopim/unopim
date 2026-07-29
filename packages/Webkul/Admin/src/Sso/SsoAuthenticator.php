<?php

namespace Webkul\Admin\Sso;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Contracts\SsoProvider;
use Webkul\Admin\Exceptions\SsoAuthenticationException;
use Webkul\User\Contracts\Admin;
use Webkul\User\Repositories\AdminRepository;

class SsoAuthenticator
{
    public function __construct(protected readonly AdminRepository $adminRepository) {}

    /**
     * Log the resolved identity in against an existing admin account. Accounts are
     * never provisioned here; listeners on identity.resolved may do so explicitly.
     *
     * @throws SsoAuthenticationException
     */
    public function authenticate(SsoIdentity $identity, SsoProvider $provider, Request $request): Admin
    {
        Event::dispatch('unopim.admin.sso.identity.resolved', [$identity, $provider]);

        $email = mb_strtolower(trim($identity->email));

        $admin = $this->adminRepository->getModel()
            ->newQuery()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $admin || $admin->isApiUser()) {
            throw SsoAuthenticationException::rejected($identity->email);
        }

        if (! $admin->status) {
            throw SsoAuthenticationException::inactive($identity->email);
        }

        Event::dispatch('unopim.admin.sso.login.before', [$admin, $provider]);

        auth()->guard('admin')->login($admin);

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        Event::dispatch('unopim.admin.sso.login.after', [$admin, $provider]);

        return $admin;
    }
}
