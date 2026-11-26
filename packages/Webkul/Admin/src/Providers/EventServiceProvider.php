<?php

namespace Webkul\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Admin\Listeners\Admin;
use Webkul\Admin\Listeners\OidcIdentityListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'admin.password.update.after' => [
            [Admin::class, 'afterPasswordUpdated'],
        ],

        'unopim.admin.sso.identity.resolved' => [
            [OidcIdentityListener::class, 'handle'],
        ],

        'unopim.admin.sso.login.after' => [
            [OidcIdentityListener::class, 'rememberIdToken'],
        ],
    ];
}
