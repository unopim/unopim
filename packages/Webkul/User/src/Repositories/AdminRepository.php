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
     * Whether an active admin outside the given roles and admins keeps full access.
     *
     * @param  array<int, int>  $exceptRoleIds
     * @param  array<int, int>  $exceptAdminIds
     */
    public function hasOtherActiveFullAccessAdmin(array $exceptRoleIds = [], array $exceptAdminIds = []): bool
    {
        return $this->fullAccessAdminsQuery()
            ->where('admins.status', 1)
            ->when($exceptRoleIds !== [], fn (Builder $query) => $query->whereNotIn('admins.role_id', $exceptRoleIds))
            ->when($exceptAdminIds !== [], fn (Builder $query) => $query->whereNotIn('admins.id', $exceptAdminIds))
            ->exists();
    }

    /**
     * Ids of the active admins whose role grants every permission.
     *
     * @return array<int, int>
     */
    public function activeFullAccessAdminIds(): array
    {
        return $this->fullAccessAdminsQuery()
            ->where('admins.status', 1)
            ->pluck('admins.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
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
