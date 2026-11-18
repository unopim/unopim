<?php

namespace Webkul\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Core\Eloquent\Repository;
use Webkul\User\Contracts\Role;
use Webkul\User\Services\RoleAccessGuard;

class RoleRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Role::class;
    }

    /**
     * Lock the role and every full-access role for the current transaction, in id order, then return the role.
     */
    public function lockForFullAccessChange(int $id): Role
    {
        $this->lockFullAccessRoles($id);

        return $this->getModel()->newQuery()->findOrFail($id);
    }

    /**
     * Lock every full-access role, and the given role, for the current transaction, in id order.
     *
     * Every write that can take full access away from an admin takes this lock first,
     * so those writes run one after another and each sees the others' result.
     */
    public function lockFullAccessRoles(?int $alsoLockId = null): void
    {
        $this->getModel()->newQuery()
            ->where('permission_type', RoleAccessGuard::FULL_ACCESS)
            ->when($alsoLockId !== null, fn (Builder $query) => $query->orWhereKey($alsoLockId))
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');
    }
}
