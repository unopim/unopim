<?php

namespace Webkul\Product\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Product\Listeners\CopyVariantStructure;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'catalog.attribute_family.copied' => [
            CopyVariantStructure::class.'@handle',
        ],
    ];
}
