<?php

namespace Webkul\Completeness\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Completeness\Console\RecalculateCompletenessCommand;
use Webkul\Completeness\Observers\Product as CompletenessProductObserver;
use Webkul\Product\Models\ProductProxy;

class CompletenessServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'completeness');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'completeness');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(ModuleServiceProvider::class);

        ProductProxy::observe(CompletenessProductObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                RecalculateCompletenessCommand::class,
            ]);
        }
    }
}
