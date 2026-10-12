<?php

namespace Webkul\AdminApi\Repositories;

use Webkul\AdminApi\Models\Apikey;
use Webkul\AdminApi\Services\ApiUserProvisioner;
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

    /**
     * Provision a robot admin and bind a new API key to it.
     */
    public function create(array $data): Apikey
    {
        $provision = app(ApiUserProvisioner::class)->provisionForIntegration($data['name']);

        $apiKey = parent::create([
            'name'            => $data['name'],
            'admin_id'        => $provision['admin']->id,
            'permission_type' => $data['permission_type'],
            'permissions'     => $data['permissions'] ?? [],
        ]);

        // Transient, not persisted — surfaced once to the create flow.
        $apiKey->plainEmail = $provision['admin']->email;
        $apiKey->plainPassword = $provision['password'];

        return $apiKey;
    }
}
