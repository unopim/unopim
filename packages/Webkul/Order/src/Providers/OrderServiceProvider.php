<?php

namespace Webkul\Order\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Order\Services\OrderSyncService;
use Webkul\Order\Services\ProfitabilityCalculator;
use Webkul\Order\Services\WebhookProcessor;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadRoutesFrom(__DIR__.'/../Routes/api-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'order');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'order');

        $this->app->register(EventServiceProvider::class);

        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->registerServices();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );
    }

    /**
     * Register singleton services.
     *
     * @return void
     */
    protected function registerServices(): void
    {
        $this->app->singleton(OrderSyncService::class, function ($app) {
            return new OrderSyncService();
        });

        $this->app->singleton(ProfitabilityCalculator::class, function ($app) {
            return new ProfitabilityCalculator();
        });

        $this->app->singleton(WebhookProcessor::class, function ($app) {
            return new WebhookProcessor();
        });
    }
}
