<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;

    /**
     * Set the currently logged in user for the application.
     *
     * Switching to another user mid-test drops the previous user's password
     * hash, which AuthenticateSession would otherwise treat as a changed password.
     *
     * @param  string|null  $guard
     * @return $this
     */
    public function be(Authenticatable $user, $guard = null)
    {
        $auth = $this->app['auth'];

        $guardName = $guard ?? $auth->getDefaultDriver();

        if ($auth->guard($guardName)->id() !== $user->getAuthIdentifier() && $this->app->bound('session.store')) {
            $this->app['session.store']->forget('password_hash_'.$guardName);
        }

        return parent::be($user, $guard);
    }
}
