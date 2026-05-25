<?php

namespace Webkul\AdminApi\Tests\Traits;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Passport\ClientRepository;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;

trait ApiHelperTrait
{
    /**
     * Generate authentication token and return header for the user
     */
    public function getAuthenticationHeaders(string $permissionType = 'all', mixed $permissions = null): array
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $admin = Admin::factory()->create(['password' => bcrypt('password')]);

        $clientRepo = new ClientRepository;

        $client = $clientRepo->createPasswordGrantClient(
            $admin->id, 'Client for Testing the api', env('APP_URL'), 'admins'
        );

        Apikey::factory()->create([
            'permission_type' => $permissionType,
            'admin_id'        => $admin->id,
            'oauth_client_id' => $client->getKey(),
            'permissions'     => $permissions,
        ]);

        $this->accessToken = $this->postJson('/oauth/token', [
            'grant_type'    => 'password',
            'client_id'     => $client->id,
            'client_secret' => $client->plainSecret,
            'username'      => $admin->email,
            'password'      => 'password',
            'scope'         => '',
        ])->json('access_token');

        return [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Table name to use with assertDatabaseHas
     */
    public function getFullTableName($className): string
    {
        return app($className)->getTable();
    }
}
