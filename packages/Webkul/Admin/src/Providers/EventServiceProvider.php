<?php

namespace Webkul\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'admin.password.update.after' => [
            'Webkul\Admin\Listeners\Admin@afterPasswordUpdated',
        ],
    ];
}
