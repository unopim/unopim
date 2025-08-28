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

    public function update(array $data, $id)
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if (isset($data['oauth_client_id'])) {
            
                if (!is_string($data['oauth_client_id']) || !preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                    $data['oauth_client_id']
                )) {

                    $data['oauth_client_id'] = DB::table('oauth_clients')->where('id', $data['oauth_client_id'])->value('id');
                }
            }

            if (!array_key_exists('revoked', $data) || $data['revoked'] === null) {
                $data['revoked'] = false;
            }
        }

        $model = $this->find($id);

        return $model->update($data);
    }

    
}
