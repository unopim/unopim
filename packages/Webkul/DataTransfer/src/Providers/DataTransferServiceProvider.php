<?php

namespace Webkul\DataTransfer\Providers;

use Illuminate\Support\ServiceProvider;

class DataTransferServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'data_transfer');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/importers.php', 'importers');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/exporters.php', 'exporters');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/actions.php', 'import_settings');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/actions.php', 'export_settings');
    }
}
