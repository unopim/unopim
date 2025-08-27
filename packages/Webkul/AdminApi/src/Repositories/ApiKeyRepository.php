<?php

namespace Webkul\AdminApi\Repositories;

use Illuminate\Support\Facades\DB;
use Webkul\AdminApi\Models\Apikey;
use Webkul\Core\Eloquent\Repository;

class ApiKeyRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Apikey::class;
    }

    public function create(array $data)
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':
                if (! array_key_exists('revoked', $data) || $data['revoked'] === null) {
                    $data['revoked'] = false;
                }
                break;
        }

        return $this->model->create($data);
    }
    
}
