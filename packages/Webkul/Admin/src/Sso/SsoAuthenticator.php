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

        $admin = $this->resolveAdmin($identity, $provider);

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

    /**
     * Prefer the provider's immutable subject id. Email is only used to link an
     * account the first time, because directories reassign addresses and a reused
     * address would otherwise inherit the previous holder's admin account.
     */
    protected function resolveAdmin(SsoIdentity $identity, SsoProvider $provider): ?Admin
    {
        $admin = $this->adminRepository->getModel()
            ->newQuery()
            ->where('sso_provider', $provider->getCode())
            ->where('sso_identifier', $identity->identifier)
            ->first();

        if ($admin) {
            return $admin;
        }

        $admin = $this->adminRepository->getModel()
            ->newQuery()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($identity->email))])
            ->first();

        if (! $admin) {
            return null;
        }

        if (
            $admin->sso_provider === $provider->getCode()
            && $admin->sso_identifier !== null
            && $admin->sso_identifier !== $identity->identifier
        ) {
            throw SsoAuthenticationException::rejected($identity->email);
        }

        $admin->forceFill([
            'sso_provider'   => $provider->getCode(),
            'sso_identifier' => $identity->identifier,
        ])->save();

        return $admin;
    }
}
