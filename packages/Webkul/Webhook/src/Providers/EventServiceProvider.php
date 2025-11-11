<?php

namespace Webkul\Webhook\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen('catalog.product.update.after', 'Webkul\Webhook\Listeners\Product@afterUpdate');

        Event::listen('catalog.product.create.after', 'Webkul\Webhook\Listeners\Product@afterCreate');

        Event::listen('data_transfer.imports.batch.product.save.after', 'Webkul\Webhook\Listeners\Product@afterBulkUpdate');

        Event::listen('data_transfer.imports.batch.import.before', 'Webkul\Webhook\Listeners\ImportBatch');
    }
}
