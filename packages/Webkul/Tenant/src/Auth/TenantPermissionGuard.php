<?php

namespace Webkul\Tenant\Auth;

/**
 * TenantPermissionGuard ensures tenant-scoped roles cannot access
 * platform-reserved permissions, and filters the available permission
 * set based on the user's tenant context.
 */
class TenantPermissionGuard
{
    /**
     * Check if a permission is platform-reserved.
     */
    public function isPlatformReserved(string $permission): bool
    {
        $prefixes = config('tenant-roles.platform_reserved_prefixes', []);

        foreach ($prefixes as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user is a tenant-scoped user (has a tenant_id).
     */
    public function isTenantUser($user): bool
    {
        if (! $user) {
            return false;
        }

        return ! is_null($user->tenant_id);
    }

    /**
     * Check if a user is a platform user (no tenant_id).
     */
    public function isPlatformUser($user): bool
    {
        if (! $user) {
            return false;
        }

        return is_null($user->tenant_id);
    }

    /**
     * Check if a user is allowed to have a specific permission.
     * Tenant users are denied platform-reserved permissions.
     */
    public function isAllowed($user, string $permission): bool
    {
        if ($this->isPlatformUser($user)) {
            return true;
        }

        return ! $this->isPlatformReserved($permission);
    }

    /**
     * Filter a list of permissions to only those allowed for the user.
     */
    public function filterPermissions($user, array $permissions): array
    {
        if ($this->isPlatformUser($user)) {
            return $permissions;
        }

        return array_values(array_filter($permissions, function ($permission) {
            return ! $this->isPlatformReserved($permission);
        }));
    }

    /**
     * Check if a role's scope matches its tenant context.
     * Platform-scope roles must have tenant_id = NULL.
     * Tenant-scope roles must have a tenant_id.
     */
    public function isRoleScopeValid($role): bool
    {
        $lockedRoles = config('tenant-roles.locked_roles', []);

        if (! $role->code || ! isset($lockedRoles[$role->code])) {
            return true;
        }

        $expectedScope = $lockedRoles[$role->code]['scope'];

        if ($expectedScope === 'platform') {
            return is_null($role->tenant_id);
        }

        return ! is_null($role->tenant_id);
    }
}
