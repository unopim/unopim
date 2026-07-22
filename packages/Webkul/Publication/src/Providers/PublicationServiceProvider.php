<?php

namespace Webkul\Publication\Providers;

use Illuminate\Support\ServiceProvider;

class PublicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/publication.php', 'publication');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'publication');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(ModuleServiceProvider::class);
    }
}
