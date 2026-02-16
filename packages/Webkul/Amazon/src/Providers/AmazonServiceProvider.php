<?php

namespace Webkul\Amazon\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AmazonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/amazon-routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migration');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'amazon');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'amazon');
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
