<?php

namespace Webkul\AdminApi\Repositories;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository as BaseClientRepository;
use Laravel\Passport\Passport;

class ClientRepository extends BaseClientRepository
{
    /**
     * Get a client by the given ID.
     */
    public function find(string|int $id): ?Client
    {
        $client = Passport::client();

        $client = $client->where($client->getKeyName(), $id)->first();
        if (request()->has('username')) {
            $username = request()->input('username');
            $user = $client?->admins()->where('email', $username)->get()->first();

            if (! $user) {
                $client = null;
            }
        }

        return $client;
    }

    /**
     * Get an active client by the given ID.
     */
    public function findActive(string|int $id): ?Client
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }
}
