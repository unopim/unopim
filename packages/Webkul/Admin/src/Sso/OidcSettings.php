<?php

namespace Webkul\Admin\Sso;

/**
 * Single source of the OpenID Connect settings. A value set through the
 * environment (mapped to `services.oidc_sso.*`) wins over the value stored in
 * System Settings, which wins over the built-in default. `sso_only` is read from
 * the environment only, so unsetting it always restores password sign-in even
 * when the identity provider is unreachable.
 */
class OidcSettings
{
    public const CONFIG_PREFIX = 'general.oidc_sso.settings.';

    public const ENV_PREFIX = 'services.oidc_sso.';

    public const DEFAULT_SCOPES = 'openid profile email';

    public const DEFAULT_GROUPS_CLAIM = 'groups';

    /**
     * Whether sign-in is switched on and issuer, client id and client secret are all set.
     */
    public function isEnabled(): bool
    {
        return $this->bool('enabled', false)
            && $this->issuer() !== ''
            && $this->clientId() !== ''
            && $this->clientSecret() !== '';
    }

    /**
     * Configured issuer URL, empty when unset.
     */
    public function issuer(): string
    {
        return $this->string('issuer');
    }

    /**
     * OAuth client id registered at the provider.
     */
    public function clientId(): string
    {
        return $this->string('client_id');
    }

    /**
     * OAuth client secret registered at the provider.
     */
    public function clientSecret(): string
    {
        return $this->string('client_secret');
    }

    /**
     * Requested scopes, space or comma separated in the source, always including `openid`.
     *
     * @return array<int, string>
     */
    public function scopes(): array
    {
        $configured = preg_split('/[\s,]+/', $this->string('scopes') ?: self::DEFAULT_SCOPES, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(['openid', ...$configured]));
    }

    /**
     * Sign-in button label, falling back to the translated default.
     */
    public function label(): string
    {
        return $this->string('label') ?: trans('admin::app.users.sessions.sso-sign-in-with-oidc');
    }

    /**
     * Whether only addresses the provider marks as verified are accepted; on by default.
     */
    public function requiresVerifiedEmail(): bool
    {
        return $this->bool('require_verified_email', true);
    }

    /**
     * Whether unknown users get an admin account on first sign-in.
     */
    public function autoProvisions(): bool
    {
        return $this->bool('auto_provision', false);
    }

    /**
     * Name of the role for provisioned admins when no role map applies.
     */
    public function defaultRole(): string
    {
        return $this->string('default_role');
    }

    /**
     * Claim that carries the user's groups, `groups` by default.
     */
    public function groupsClaim(): string
    {
        return $this->string('groups_claim') ?: self::DEFAULT_GROUPS_CLAIM;
    }

    /**
     * Whether a group-to-role map is configured.
     */
    public function hasRoleMap(): bool
    {
        return $this->string('role_map') !== '';
    }

    /**
     * IdP group => UnoPim role name, in configured order. A configured map that
     * is not a JSON object of strings yields an empty map, so every login is
     * rejected instead of silently skipping the mapping.
     *
     * @return array<string, string>
     */
    public function roleMap(): array
    {
        $decoded = json_decode($this->string('role_map'), true);

        if (! is_array($decoded) || (array_is_list($decoded) && $decoded !== [])) {
            return [];
        }

        $map = [];

        foreach ($decoded as $group => $role) {
            if (is_string($role) && trim($role) !== '' && trim((string) $group) !== '') {
                $map[(string) $group] = trim($role);
            }
        }

        return $map;
    }

    /**
     * Whether logout also ends the session at the provider; on by default.
     */
    public function performsRpLogout(): bool
    {
        return $this->bool('rp_logout', true);
    }

    /**
     * Password sign-in is switched off only while the provider itself is usable.
     */
    public function isSsoOnly(): bool
    {
        return $this->toBool(config(self::ENV_PREFIX.'sso_only')) === true && $this->isEnabled();
    }

    /**
     * Value from the environment when set there, else from System Settings.
     */
    protected function raw(string $name): mixed
    {
        $value = config(self::ENV_PREFIX.$name);

        if ($value !== null && $value !== '') {
            return $value;
        }

        return core()->getConfigData(self::CONFIG_PREFIX.$name);
    }

    /**
     * Trimmed string value, the environment winning over the stored setting.
     */
    protected function string(string $name): string
    {
        $value = $this->raw($name);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Boolean value, the environment winning over the stored setting, else the default.
     */
    protected function bool(string $name, bool $default): bool
    {
        return $this->toBool($this->raw($name)) ?? $default;
    }

    /**
     * Interpret a config or environment value as a boolean, null when it is unset or unreadable.
     */
    protected function toBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
