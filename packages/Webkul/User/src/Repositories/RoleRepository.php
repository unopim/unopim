<?php

namespace Webkul\User\Repositories;

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
        $this->getModel()->newQuery()
            ->where('permission_type', RoleAccessGuard::FULL_ACCESS)
            ->orWhereKey($id)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');

        return $this->getModel()->newQuery()->findOrFail($id);
    }
}
