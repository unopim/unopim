<?php

namespace Webkul\Product\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'catalog.product.update.after'  => [
            'Webkul\Product\Listeners\Product@afterUpdate',
        ],
        'catalog.product.delete.before' => [
            'Webkul\Product\Listeners\Product@beforeDelete',
        ],
    ];
}
