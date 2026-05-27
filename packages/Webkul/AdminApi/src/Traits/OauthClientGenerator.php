<?php

namespace Webkul\AdminApi\Traits;

use Webkul\AdminApi\Models\Client;
use Webkul\User\Models\Admin;

trait OauthClientGenerator
{
    /**
     * Generates a new OAuth client ID and secret key for the specified admin user.
     *
     * @return \Laravel\Passport\Client The newly created OAuth client with the generated client ID and secret key.
     */
    public function generateClientIdAndSecretKey(int $user_id, string $name)
    {
        $provider = config('auth.guards.api.provider', 'admins');

        // Passport 13 dropped the $userId + $redirect args from
        // createPasswordGrantClient. Create the client, then attach the
        // owner morph (owner_type / owner_id) for Passport 13's polymorphic
        // owner() relation and keep the legacy user_id column populated for
        // backwards-compat with ApiKeysDataGrid JOINs and Client::admins().
        $client = $this->clients->createPasswordGrantClient(
            $name,
            $provider,
            confidential: true,
        );

        $client->forceFill([
            'user_id'    => $user_id,
            'owner_type' => Admin::class,
            'owner_id'   => $user_id,
        ])->save();

        return $client;
    }

    /**
     * Regenerates the secret key for the specified OAuth client.
     *
     * @return Client The updated OAuth client object with the regenerated secret key.
     */
    public function regenerateSecret(Client $client)
    {
        // Passport 13 regenerateSecret() returns bool (save() result), not the
        // mutated Client. The $client passed in is mutated in place — return it.
        $this->clients->regenerateSecret($client);

        return $client;
    }

    /**
     * Masks a client ID or secret key by replacing a portion of the string with 'x'.
     *
     * @return string The masked client ID or secret key. If the input string is too short to mask,
     *                the original string is returned.
     */
    public function maskClientIdAndScreatKey(string $value, int $startLength = 3, int $endLength = 3)
    {
        $clientIdLength = strlen($value);

        // Ensure the start and end lengths are not greater than the total length of the client ID
        if ($startLength + $endLength >= $clientIdLength) {
            return $value; // Return the original client ID if it is too short to mask
        }

        // Extract the start and end parts
        $start = substr($value, 0, $startLength);
        $end = substr($value, -$endLength);

        // Calculate the number of 'x's to insert
        $maskLength = $clientIdLength - ($startLength + $endLength);
        $mask = str_repeat('x', $maskLength);

        // Combine the start, mask, and end parts
        return $start.$mask.$end;
    }
}
