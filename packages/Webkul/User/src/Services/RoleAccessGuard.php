<?php

namespace Webkul\User\Services;

use Webkul\User\Contracts\Admin;
use Webkul\User\Contracts\Role;
use Webkul\User\Repositories\AdminRepository;

/**
 * The single authority for who may give or assign role access, shared by the
 * role and user screens and the role and user importers. A missing actor holds
 * no permissions: a job whose admin is unknown or deleted can grant nothing, and
 * a console run that must grant access names the admin it acts for.
 */
class RoleAccessGuard
{
    public const FULL_ACCESS = 'all';

    public const CUSTOM_ACCESS = 'custom';

    public function __construct(protected AdminRepository $adminRepository) {}

    /**
     * Whether the role grants every permission.
     */
    public function hasFullAccess(?Role $role): bool
    {
        return $role?->permission_type === self::FULL_ACCESS;
    }

    /**
     * Translation key explaining why the actor cannot give a role this access, or null when it can.
     *
     * @param  array<int, mixed>  $permissions
     */
    public function grantError(?Admin $actor, mixed $permissionType, array $permissions): ?string
    {
        if ($this->hasFullAccess($actor?->role)) {
            return null;
        }

        if ($permissionType !== self::CUSTOM_ACCESS
            || array_filter($permissions, is_string(...)) !== $permissions
            || array_diff($permissions, $actor?->role?->permissions ?? []) !== []) {
            return 'admin::app.settings.roles.cannot-grant-unheld-permissions';
        }

        return null;
    }

    /**
     * Translation key explaining why the actor cannot change or remove the role's current access, or null when it can.
     */
    public function currentAccessError(?Admin $actor, ?Role $role): ?string
    {
        if (! $role instanceof Role) {
            return null;
        }

        return $this->grantError($actor, $role->permission_type, (array) ($role->permissions ?? []));
    }

    /**
     * Translation key explaining why the actor cannot assign the role to a user, or null when it can.
     */
    public function assignmentError(?Admin $actor, ?Role $targetRole): ?string
    {
        if ($this->hasFullAccess($actor?->role)) {
            return null;
        }

        if (! $targetRole instanceof Role || $this->hasFullAccess($targetRole)) {
            return 'admin::app.settings.users.cannot-escalate-role';
        }

        if (array_diff($targetRole->permissions ?? [], $actor?->role?->permissions ?? []) !== []) {
            return 'admin::app.settings.users.cannot-assign-unheld-permissions';
        }

        return null;
    }

    /**
     * Translation key explaining why the actor lacks the permission, or null when it holds it.
     */
    public function permissionError(?Admin $actor, string $permission): ?string
    {
        if ($this->hasFullAccess($actor?->role) || $actor?->hasPermission($permission)) {
            return null;
        }

        return 'admin::app.common.unauthorized';
    }

    /**
     * The admin who started a job, with their role, or null when none is recorded.
     */
    public function actor(?int $adminId): ?Admin
    {
        return $adminId ? $this->adminRepository->with('role')->find($adminId) : null;
    }
}
