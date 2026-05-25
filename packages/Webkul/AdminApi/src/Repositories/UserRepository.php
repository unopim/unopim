<?php

namespace Webkul\AdminApi\Repositories;

use Laravel\Passport\Bridge\User;
use Laravel\Passport\Bridge\UserRepository as BaseUserRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Webkul\User\Models\Admin;

/**
 * Custom UserRepository for Unopim that uses the Admin model
 * instead of the default App\Models\User.
 *
 * This repository is bound in the service container to ensure
 * that Passport's OAuth2 password grant uses the correct user model
 * when authenticating admin users.
 */
class UserRepository extends BaseUserRepository
{
    /**
     * Get a user entity by user credentials.
     *
     * @param  string  $username
     * @param  string  $password
     * @param  string  $grantType
     * @param  ClientEntityInterface  $clientEntity
     * @return UserEntityInterface|null
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        $clientEntity
    ) {
        // Find the admin user by email
        $user = Admin::where('email', $username)->first();

        if (! $user) {
            return null;
        }

        // Verify the password is correct
        if (! $this->hasher->check($password, $user->password)) {
            return null;
        }

        // Check if user account is active
        if ($user->status === 0) {
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
