<?php

namespace Webkul\Notification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Webkul\Notification\Events\NotificationEvent::class => [
            \Webkul\Notification\Listeners\NotificationListener::class,
        ],

        'data_transfer.export.completed' => [
            'Webkul\Notification\Listeners\SendNotificationListener@sendNotification',
        ],

        'data_transfer.imports.completed' => [
            'Webkul\Notification\Listeners\SendNotificationListener@sendNotification',
        ],

        'data_transfer.import.validate.state_failed' => [
            'Webkul\Notification\Listeners\SendNotificationListener@sendNotification',
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
