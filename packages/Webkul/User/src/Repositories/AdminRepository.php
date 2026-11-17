<?php

namespace Webkul\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Core\Eloquent\Repository;
use Webkul\User\Contracts\Admin;

class AdminRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Admin::class;
    }

    /**
     * Count admins with all access.
     */
    public function countAdminsWithAllAccess(): int
    {
        return $this->fullAccessAdminsQuery()->count();
    }

    /**
     * Whether an active admin outside the given role keeps full access.
     */
    public function hasOtherActiveFullAccessAdmin(?int $exceptRoleId = null): bool
    {
        return $this->fullAccessAdminsQuery()
            ->where('admins.status', 1)
            ->when($exceptRoleId !== null, fn (Builder $query) => $query->where('admins.role_id', '!=', $exceptRoleId))
            ->exists();
    }

    /**
     * Count admins with all access and active status.
     */
    public function countAdminsWithAllAccessAndActiveStatus(): int
    {
        return $this->fullAccessAdminsQuery()
            ->where('admins.status', 1)
            ->count();
    }

    /**
     * Admins whose role grants every permission.
     */
    protected function fullAccessAdminsQuery(): Builder
    {
        return $this->getModel()->newQuery()
            ->join('roles', 'admins.role_id', '=', 'roles.id')
            ->where('roles.permission_type', 'all');
    }
}
