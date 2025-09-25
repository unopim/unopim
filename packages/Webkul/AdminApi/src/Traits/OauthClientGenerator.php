<?php

namespace Webkul\AdminApi\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

trait OauthClientGenerator
{
    /**
     * Generates a new OAuth client ID and secret key for the specified admin user.
     *
     * @return \Laravel\Passport\Client The newly created OAuth client with the generated client ID and secret key.
     */
    public function generateClientIdAndSecretKey(int $user_id, string $name)
    {
        $providers = array_keys(config('auth.providers'));
        $provider = $providers[0];

        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':
                $client = new Client;
                $client->id = Str::uuid()->toString();
                $client->user_id = $user_id;
                $client->name = $name;
                $client->secret = Str::random(40);
                $client->provider = $provider;
                $client->redirect = 'http://localhost';
                $client->personal_access_client = false;
                $client->password_client = true;
                $client->revoked = false;
                $client->save();
                break;

            case 'mysql':
            default:
                $client = $this->clients->createPasswordGrantClient(
                    $user_id,
                    $name,
                    'http://localhost',
                    $provider
                );
                break;
        }

        return $client;
    }

    /**
     * Regenerates the secret key for the specified OAuth client.
     *
     * @return Client The updated OAuth client object with the regenerated secret key.
     */
    public function regenerateSecret(Client $client)
    {
        $client = $this->clients->regenerateSecret($client);

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
