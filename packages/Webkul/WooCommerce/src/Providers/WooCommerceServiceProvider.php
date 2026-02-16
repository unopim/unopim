<?php

namespace Webkul\WooCommerce\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class WooCommerceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/woocommerce-routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migration');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'woocommerce');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'woocommerce');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
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
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/exporters.php',
            'exporters'
        );
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/importers.php',
            'importers'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../Config/unopim-vite.php',
            'unopim-vite.viters'
        );
    }
}
