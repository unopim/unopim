<?php

namespace Webkul\ElasticSearch\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\ElasticSearch\Console\Command\ProductIndexer;
use Webkul\ElasticSearch\Console\Command\CategoryIndexer;
use Webkul\ElasticSearch\Observers\Product;
use Webkul\ElasticSearch\Observers\Category;
use Webkul\Product\Models\Product as Products;
use Webkul\Category\Models\Category as Categories;

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
        Products::observe(Product::class);
        Categories::observe(Category::class);
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
                ProductIndexer::class,
                CategoryIndexer::class,
            ]);
        }
    }
}
