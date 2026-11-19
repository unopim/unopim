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
     * Active admins whose role grants every permission, keyed by email; locked for the current transaction when asked.
     *
     * @return array<string, array{id: int, role_id: int}>
     */
    public function activeFullAccessAdmins(bool $lock = false): array
    {
        return $this->fullAccessAdminsQuery()
            ->where('admins.status', 1)
            ->when($lock, fn (Builder $query) => $query->orderBy('admins.id')->lockForUpdate())
            ->get(['admins.id', 'admins.email', 'admins.role_id'])
            ->mapWithKeys(fn ($admin): array => [$admin->email => ['id' => (int) $admin->id, 'role_id' => (int) $admin->role_id]])
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
