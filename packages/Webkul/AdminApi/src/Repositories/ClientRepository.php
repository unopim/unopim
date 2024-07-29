<?php

namespace Webkul\AdminApi\Repositories;

use Laravel\Passport\ClientRepository as BaseClientRepository;
use Laravel\Passport\Passport;

class ClientRepository extends BaseClientRepository
{
    /**
     * Get a client by the given ID.
     *
     * @param  int|string  $id
     * @return \Laravel\Passport\Client|null
     */
    public function find($id)
    {
        $client = Passport::client();

        $client = $client->where($client->getKeyName(), $id)->first();
        if (request()->has('username')) {
            $username = request()->get('username');
            $user = $client?->admins()->where('email', $username)->get()->first();

            if (! $user) {
                $client = null;
            }
        }

        return $client;
    }

    /**
     * Get an active client by the given ID.
     *
     * @param  int|string  $id
     * @return \Laravel\Passport\Client|null
     */
    public function findActive($id)
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }
}
