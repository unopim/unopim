<?php

namespace Webkul\User\Repositories;

use Webkul\Core\Eloquent\Repository;

class RoleRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\User\Contracts\Role';
    }

    /**
     * Create a new role safely for both MySQL & PostgreSQL
     */
    public function create(array $data)
    {
        $this->model->create($data);
    }
}
