<?php

namespace Webkul\Notification\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Notification\Events\NotificationEvent;
use Webkul\Notification\Listeners\NotificationListener;
use Webkul\Notification\Listeners\SendNotificationListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NotificationEvent::class => [
            NotificationListener::class,
        ],

        'data_transfer.export.completed' => [
            [SendNotificationListener::class, 'sendNotification'],
        ],

        'data_transfer.imports.completed' => [
            [SendNotificationListener::class, 'sendNotification'],
        ],

        'data_transfer.import.validate.state_failed' => [
            [SendNotificationListener::class, 'sendNotification'],
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
