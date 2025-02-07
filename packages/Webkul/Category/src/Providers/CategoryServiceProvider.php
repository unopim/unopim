<?php

namespace Webkul\Category\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Category\Observers\CategoryObserver;
use Webkul\Category\Services\CategoryAdditionalDataMapper;

class CategoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        CategoryProxy::observe(CategoryObserver::class);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->registerFacades();
    }

    /**
     * Register configuration.
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/category_field_types.php', 'category_field_types');
    }

    /**
     * Register Bouncer as a singleton.
     */
    protected function registerFacades(): void
    {
        /**
         * Product value mapper
         */
        $this->app->singleton('category_additional_data_mapper', function ($app) {
            return new CategoryAdditionalDataMapper;
        });
    }
}
