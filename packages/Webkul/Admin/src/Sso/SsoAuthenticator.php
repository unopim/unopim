<?php

namespace Webkul\Admin\Sso;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
     * address would otherwise inherit the previous holder's admin account. An
     * account already bound to a subject, by this or any other driver, is never
     * re-bound by email, so one driver cannot take over another driver's binding.
     * The link is only written for an account that may sign in, so a refused
     * login never leaves a binding behind.
     */
    protected function resolveAdmin(SsoIdentity $identity, SsoProvider $provider): ?Admin
    {
        $admin = $this->adminBySubject($provider->getCode(), $identity->identifier);

        if ($admin) {
            return $admin;
        }

        $admin = $this->adminByEmail($identity->email);

        if (! $admin) {
            return null;
        }

        $this->ensureLinkable($admin, $identity);

        $admin->forceFill([
            'sso_provider'   => $provider->getCode(),
            'sso_identifier' => $identity->identifier,
        ])->save();

        return $admin;
    }

    /**
     * Refuse linking an email-matched admin by subject when the account is
     * already bound, is an API user or is inactive, before anything is written.
     *
     * @throws SsoAuthenticationException
     */
    public function ensureLinkable(Admin $admin, SsoIdentity $identity): void
    {
        if ($admin->sso_identifier !== null || $admin->isApiUser()) {
            throw SsoAuthenticationException::rejected($identity->email);
        }

        if (! $admin->status) {
            throw SsoAuthenticationException::inactive($identity->email);
        }
    }

    /**
     * Admin bound to the subject. The database only narrows the candidates; the
     * match itself is an exact string comparison in PHP, so a case- or
     * accent-insensitive column collation cannot let a look-alike subject in.
     */
    public function adminBySubject(string $provider, string $identifier): ?Admin
    {
        return $this->adminRepository->getModel()
            ->newQuery()
            ->where('sso_provider', $provider)
            ->where('sso_identifier', $identifier)
            ->get()
            ->first(fn (Admin $admin): bool => $admin->sso_provider === $provider && $admin->sso_identifier === $identifier);
    }

    /**
     * Admin whose trimmed, lower-cased email is exactly the given one. The column
     * collation may consider `jose@cörp.com` equal to `jose@corp.com`; only the
     * exact comparison in PHP decides, so a look-alike address never matches.
     */
    public function adminByEmail(string $email): ?Admin
    {
        $needle = $this->normalizeEmail($email);

        return $this->adminsCollatingWithEmail($email)
            ->first(fn (Admin $admin): bool => $this->normalizeEmail((string) $admin->email) === $needle);
    }

    /**
     * Whether the column collation already considers the address taken, exact
     * match or look-alike. A new account cannot be created for it then.
     */
    public function emailCollidesWithAdmin(string $email): bool
    {
        return $this->adminsCollatingWithEmail($email)->isNotEmpty();
    }

    /**
     * @return Collection<int, Admin>
     */
    protected function adminsCollatingWithEmail(string $email): Collection
    {
        return $this->adminRepository->getModel()
            ->newQuery()
            ->whereRaw('LOWER(email) = ?', [$this->normalizeEmail($email)])
            ->get()
            ->toBase();
    }

    /**
     * Trimmed, lower-cased form used for exact email comparison.
     */
    protected function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
