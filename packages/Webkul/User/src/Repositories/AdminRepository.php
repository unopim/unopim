<?php

namespace Webkul\User\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class AdminRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\User\Contracts\Admin';
    }

    public function create(array $data)
    {
        Event::dispatch('user.admin.create.before');

        if (empty($data['id'])) {
            unset($data['id']);
        } else {
            $data['id'] = (int) $data['id'];
        }

        $admin = parent::create($data);

        Event::dispatch('user.admin.create.after', $admin);

        return $admin;
    }

    /**
     * Count admins with all access.
     */
    public function countAdminsWithAllAccess(): int
    {
        return $this->getModel()
            ->leftJoin('roles', 'admins.role_id', '=', 'roles.id')
            ->where('roles.permission_type', 'all')
            ->get()
            ->count();
    }

    /**
     * Count admins with all access and active status.
     */
    public function countAdminsWithAllAccessAndActiveStatus(): int
    {
        return $this->getModel()
            ->leftJoin('roles', 'admins.role_id', '=', 'roles.id')
            ->where('admins.status', 1)
            ->where('roles.permission_type', 'all')
            ->get()
            ->count();
    }
}
