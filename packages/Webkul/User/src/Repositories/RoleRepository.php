<?php

namespace Webkul\User\Repositories;

use Illuminate\Support\Facades\DB;
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
        // Ensure id is handled properly
        if (empty($data['id'])) {
            unset($data['id']);
        } else {
            $data['id'] = (int) $data['id'];
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $sequence = $this->model->getTable().'_id_seq';
            DB::statement("
            SELECT setval(
                '{$sequence}',
                (SELECT COALESCE(MAX(id), 0) + 1 FROM {$this->model->getTable()}),
                false
            )
        ");
        }

        $role = parent::create($data);

        return $role;
    }
}
