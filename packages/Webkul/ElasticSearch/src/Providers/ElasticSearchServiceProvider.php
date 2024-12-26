<?php

namespace Webkul\ElasticSearch\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\ElasticSearch\Console\Command\Indexer;
use Webkul\ElasticSearch\Observers\ProductObserver;
use Webkul\Product\Models\Product;

class ElasticSearchServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Product::observe(ProductObserver::class);
    }

    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register the Installer Commands of this package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Indexer::class,
            ]);
        }
    }
}
