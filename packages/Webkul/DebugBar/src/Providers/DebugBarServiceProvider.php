<?php

namespace Webkul\DebugBar\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\ServiceProvider;
use Webkul\DebugBar\DataCollector\ModuleCollector;

class DebugBarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (class_exists(Debugbar::class)) {
            Debugbar::addCollector(resolve(ModuleCollector::class));
        }
    }
}
