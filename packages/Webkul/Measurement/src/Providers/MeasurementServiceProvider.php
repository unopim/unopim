<?php

namespace Webkul\Measurement\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MeasurementServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Database/Seeders' => database_path('seeders'),
        ], 'measurement-seeders');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'measurement');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'measurement');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php',
            'attribute_types'
        );

    }

    public function register()
    {
        $this->app->register(MeasurementEventServiceProvider::class);
        $this->app->bind(
            \Webkul\DataTransfer\Helpers\Importers\FieldProcessor::class,
            \Webkul\Measurement\Helpers\Importers\FieldProcessor::class
        );
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
    }
}
