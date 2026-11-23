<?php

namespace Webkul\DebugBar\Providers;

use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Support\ServiceProvider;
use Webkul\DebugBar\DataCollector\ModuleCollector;

class DebugBarServiceProvider extends ServiceProvider
{
    /**
     * Hand the module collector to the debugbar as a custom collector so it is only built when the
     * debugbar boots; it records every query, so building it in console commands and queue workers
     * would grow memory without bound.
     */
    public function register(): void
    {
        if (! class_exists(LaravelDebugbar::class)) {
            return;
        }

        $this->app['config']->set(
            'debugbar.custom_collectors',
            $this->app['config']->get('debugbar.custom_collectors', []) + [ModuleCollector::class => true]
        );
    }
}
