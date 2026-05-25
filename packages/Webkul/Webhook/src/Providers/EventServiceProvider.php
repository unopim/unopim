<?php

namespace Webkul\Webhook\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Webhook\Listeners\ImportBatch;
use Webkul\Webhook\Listeners\Product;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen('catalog.product.update.after', [Product::class, 'afterUpdate']);

        Event::listen('catalog.product.create.after', [Product::class, 'afterCreate']);

        Event::listen('catalog.product.bulk.edit.after', [Product::class, 'afterBulkEdit']);

        Event::listen('data_transfer.imports.batch.product.save.after', [Product::class, 'afterBulkUpdate']);

        Event::listen('data_transfer.imports.batch.import.before', ImportBatch::class);
    }
}
