<?php

namespace Webkul\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Admin\Listeners\Admin;
use Webkul\Admin\Listeners\DashboardCache;

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

        'data_transfer.imports.completed' => [
            [DashboardCache::class, 'flush'],
        ],

        'data_transfer.imports.cancelled' => [
            [DashboardCache::class, 'flush'],
        ],

        'catalog.product.bulk.edit.after' => [
            [DashboardCache::class, 'flush'],
        ],

        'completeness.product.calculate.after' => [
            [DashboardCache::class, 'flushProducts'],
        ],

        'installer.demo_data.seeded' => [
            [DashboardCache::class, 'flush'],
        ],
    ];
}
