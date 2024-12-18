<?php

namespace Webkul\Notification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Webkul\Notification\Events\NotificationEvent::class => [
            \Webkul\Notification\Listeners\NotificationListener::class,
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
