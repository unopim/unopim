<?php

namespace Webkul\ElasticSearch\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Category\Models\Category as Categories;
use Webkul\ElasticSearch\Console\Command\CategoryIndexer;
use Webkul\ElasticSearch\Console\Command\ProductIndexer;
use Webkul\ElasticSearch\Console\Command\Reindexer;
use Webkul\ElasticSearch\Observers\Category;
use Webkul\ElasticSearch\Observers\Product;
use Webkul\ElasticSearch\SearchQueryBuilder;
use Webkul\Product\Models\Product as Products;

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
        $this->registerFacades();
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
                Reindexer::class,
            ]);
        }
    }

    public function registerFacades(): void
    {
        $this->app->singleton('search-query-builder', function ($app) {
            return new SearchQueryBuilder();
        });
    }
}
