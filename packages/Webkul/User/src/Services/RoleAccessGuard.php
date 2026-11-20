<?php

namespace Webkul\User\Services;

use Webkul\User\Contracts\Admin;
use Webkul\User\Contracts\Role;
use Webkul\User\Repositories\AdminRepository;

/**
 * Decides who may give or assign role access, shared by the user screens and the
 * role and user importers. A missing actor holds no permissions, so a job whose
 * admin is unknown or deleted can grant or assign nothing.
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
        if (! $actor instanceof Admin) {
            return 'admin::app.common.unauthorized';
        }

        if ($this->hasFullAccess($actor->role)) {
            return null;
        }

        if ($permissionType !== self::CUSTOM_ACCESS
            || array_filter($permissions, is_string(...)) !== $permissions
            || array_diff($permissions, $actor->role?->permissions ?? []) !== []) {
            return 'admin::app.settings.roles.cannot-grant-unheld-permissions';
        }

        return null;
    }

    /**
     * Translation key explaining why the actor cannot assign the role to a user, or null when it can.
     */
    public function assignmentError(?Admin $actor, ?Role $targetRole): ?string
    {
        if (! $actor instanceof Admin) {
            return 'admin::app.common.unauthorized';
        }

        if ($this->hasFullAccess($actor->role)) {
            return null;
        }

        if (! $targetRole instanceof Role || $this->hasFullAccess($targetRole)) {
            return 'admin::app.settings.users.cannot-escalate-role';
        }

        if (array_diff($targetRole->permissions ?? [], $actor->role?->permissions ?? []) !== []) {
            return 'admin::app.settings.users.cannot-assign-unheld-permissions';
        }

        return null;
    }

    /**
     * The active admin who started a job, with their role, or null when none is recorded or they have since been disabled.
     */
    public function actor(?int $adminId): ?Admin
    {
        $admin = $adminId ? $this->adminRepository->with('role')->find($adminId) : null;

        return $admin && $admin->status ? $admin : null;
    }
}
