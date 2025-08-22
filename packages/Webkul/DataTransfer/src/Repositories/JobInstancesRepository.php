<?php

namespace Webkul\DataTransfer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobInstances;
use Illuminate\Support\Facades\DB;

class JobInstancesRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return JobInstances::class;
    }

    /**
     * Update job instance with DB-driver specific fixes.
     */
    public function update(array $data, $id)
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if (isset($data['allowed_errors']) && $data['allowed_errors'] === '') {
                $data['allowed_errors'] = 0;
            }
        }

        return parent::update($data, $id);
    }

    /**
     * Create job instance with DB-driver specific fixes.
     */
    public function create(array $data)
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if (isset($data['allowed_errors']) && $data['allowed_errors'] === '') {
                $data['allowed_errors'] = 0;
            }
        }

        return parent::create($data);
    }
}
