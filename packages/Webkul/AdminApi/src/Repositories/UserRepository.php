<?php

namespace Webkul\AdminApi\Repositories;

use Laravel\Passport\Bridge\User;
use Laravel\Passport\Bridge\UserRepository as BaseUserRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Webkul\User\Models\Admin;

/**
 * UserRepository backing Passport's OAuth2 password grant with the Admin model instead of App\Models\User.
 */
class UserRepository extends BaseUserRepository
{
    /**
     * Get a user entity by user credentials.
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        $user = Admin::where('email', $username)->first();

        if (! $user) {
            return null;
        }

        if (! $this->hasher->check($password, $user->password)) {
            return null;
        }

        // Truthy check (not === 0) so a status string "0" from emulated prepared statements still reads as inactive.
        if (! $user->status) {
            return null;
        }

        return $this->makeUserEntity($user);
    }

    /**
     * Make a Passport user entity from an Admin model instance.
     *
     * @param  Admin  $user
     * @return User
     */
    protected function makeUserEntity($user)
    {
        return new User($user->id);
    }
}
