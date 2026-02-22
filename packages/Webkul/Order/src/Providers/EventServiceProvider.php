<?php

namespace Webkul\Order\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'order.received' => [
            'Webkul\Order\Listeners\SyncOrderToUnified',
            'Webkul\Order\Listeners\CalculateProfitability',
        ],

        'order.status.changed' => [
            'Webkul\Order\Listeners\NotifyChannelUpdates',
            'Webkul\Order\Listeners\UpdateMetrics',
        ],

        'order.profitability.calculated' => [
            'Webkul\Order\Listeners\UpdateOrderCache',
        ],
    ];
}
