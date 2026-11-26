<?php

namespace Webkul\Admin\Listeners;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Admin\Contracts\SsoProvider;
use Webkul\Admin\Exceptions\SsoAuthenticationException;
use Webkul\Admin\Sso\OidcProvider;
use Webkul\Admin\Sso\OidcSettings;
use Webkul\Admin\Sso\SsoAuthenticator;
use Webkul\Admin\Sso\SsoIdentity;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\User\Contracts\Admin;
use Webkul\User\Contracts\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Services\RoleAccessGuard;

/**
 * OpenID Connect account policy, applied before SsoAuthenticator resolves the
 * admin: the email-link guard, just-in-time provisioning and group-to-role
 * mapping. Every other SSO driver passes through untouched.
 */
class OidcIdentityListener
{
    public function __construct(
        protected OidcSettings $settings,
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected LocaleRepository $localeRepository,
        protected RoleAccessGuard $roleAccessGuard,
        protected SsoAuthenticator $authenticator,
    ) {}

    /**
     * An admin already linked by `sub` is used as is. An unlinked admin found by
     * email is only handed on for linking when the provider vouches for the
     * address (`email_verified === true`), whatever the verified-email switch
     * says, so registering someone else's address at the provider cannot take
     * over their account. An admin already bound to another subject, by any
     * driver, an API user or an inactive admin is refused before any link is
     * written. Without any match the account is
     * provisioned when enabled. With a role map configured, a login matching no
     * mapped group is refused and the role is synced on every login.
     *
     * @throws SsoAuthenticationException
     */
    public function handle(SsoIdentity $identity, SsoProvider $provider): void
    {
        if ($provider->getCode() !== 'oidc') {
            return;
        }

        $admin = $this->linkedAdmin($identity);

        if (! $admin && ($admin = $this->adminByEmail($identity))) {
            if (($identity->raw['email_verified'] ?? null) !== true) {
                throw SsoAuthenticationException::rejected($identity->email);
            }

            $this->authenticator->ensureLinkable($admin, $identity);
        }

        $role = $this->settings->hasRoleMap() ? $this->mappedRole($identity) : null;

        if (! $admin) {
            $this->provision($identity, $role);

            return;
        }

        if ($role) {
            $this->syncRole($admin, $role);
        }
    }

    /**
     * Keep the verified ID token in the new session, the `id_token_hint` and the
     * marker for RP-initiated logout.
     */
    public function rememberIdToken(Admin $admin, SsoProvider $provider): void
    {
        if ($provider instanceof OidcProvider && is_string($idToken = $provider->verifiedIdToken())) {
            session()->put(OidcProvider::ID_TOKEN_SESSION_KEY, $idToken);
        }
    }

    /**
     * Admin already bound to this OpenID Connect subject.
     */
    protected function linkedAdmin(SsoIdentity $identity): ?Admin
    {
        return $this->authenticator->adminBySubject('oidc', $identity->identifier);
    }

    /**
     * Admin whose email exactly matches the asserted address.
     */
    protected function adminByEmail(SsoIdentity $identity): ?Admin
    {
        return $this->authenticator->adminByEmail($identity->email);
    }

    /**
     * First map entry, in configured order, whose group the user belongs to and
     * whose role exists.
     *
     * @throws SsoAuthenticationException
     */
    protected function mappedRole(SsoIdentity $identity): Role
    {
        $claim = $identity->raw[$this->settings->groupsClaim()] ?? [];

        $groups = array_map('strval', array_filter((array) $claim, is_scalar(...)));

        foreach ($this->settings->roleMap() as $group => $roleName) {
            if (in_array($group, $groups, true) && ($role = $this->roleByName($roleName))) {
                return $role;
            }
        }

        throw SsoAuthenticationException::rejected($identity->email);
    }

    /**
     * Create the admin with an unusable random password, linked to the subject.
     * Without a role map the role comes from the configured default role; with
     * neither, nothing is created and the login is refused. The login is also
     * refused when the address already belongs to an admin under the column
     * collation, look-alikes included, since that admin's account must not be
     * matched or shadowed. The `user.admin.create.before/after` events fire as
     * for an admin created on the users screen.
     *
     * @throws SsoAuthenticationException
     */
    protected function provision(SsoIdentity $identity, ?Role $role): void
    {
        if (! $this->settings->autoProvisions()) {
            return;
        }

        $role ??= $this->roleByName($this->settings->defaultRole());

        if (! $role || $this->authenticator->emailCollidesWithAdmin($identity->email)) {
            throw SsoAuthenticationException::rejected($identity->email);
        }

        $localeId = $this->defaultLocaleId();

        Event::dispatch('user.admin.create.before');

        try {
            $admin = $this->adminRepository->create([
                'name'               => $identity->name ?: Str::before($identity->email, '@'),
                'email'              => $identity->email,
                'password'           => bcrypt(Str::random(64)),
                'role_id'            => $role->id,
                'status'             => 1,
                'type'               => 'user',
                'ui_locale_id'       => $localeId,
                'catalog_locale_id'  => $localeId,
                'default_channel_id' => core()->getDefaultChannel()?->id,
                'timezone'           => config('app.timezone') ?: 'UTC',
                'sso_provider'       => 'oidc',
                'sso_identifier'     => $identity->identifier,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw SsoAuthenticationException::rejected($identity->email);
        }

        Event::dispatch('user.admin.create.after', $admin);
    }

    /**
     * Apply the mapped role, except for API users and inactive admins (which the
     * authenticator refuses anyway) and except when it would demote the last
     * active admin holding full access: that sync is skipped and logged, the
     * login proceeds, and a later login applies it once another full-access
     * admin exists.
     */
    protected function syncRole(Admin $admin, Role $role): void
    {
        if ($admin->isApiUser() || ! $admin->status || (int) $admin->role_id === (int) $role->id) {
            return;
        }

        DB::transaction(function () use ($admin, $role): void {
            $this->roleRepository->lockFullAccessRoles();

            $current = $this->adminRepository->find($admin->id);

            if (
                $this->roleAccessGuard->hasFullAccess($current->role)
                && ! $this->roleAccessGuard->hasFullAccess($role)
                && ! $this->adminRepository->hasOtherActiveFullAccessAdmin(exceptAdminIds: [$current->id])
            ) {
                Log::warning('OIDC role sync skipped: it would remove the last active full-access admin.', [
                    'admin_id' => $current->id,
                    'role'     => $role->name,
                ]);

                return;
            }

            $this->adminRepository->update(['role_id' => $role->id], $current->id);
        });
    }

    /**
     * Role with exactly this name, null for an empty name.
     */
    protected function roleByName(string $name): ?Role
    {
        return $name === '' ? null : $this->roleRepository->findOneByField('name', $name);
    }

    /**
     * Id of the application locale, used as UI and catalog locale for provisioned admins.
     */
    protected function defaultLocaleId(): ?int
    {
        $locale = $this->localeRepository->findOneWhere([
            'code'   => core()->getDefaultLocaleCodeFromDefaultChannel(),
            'status' => 1,
        ]) ?? $this->localeRepository->findOneByField('status', 1);

        return $locale?->id;
    }
}
