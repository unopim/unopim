<?php

namespace Webkul\Attribute\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Services\AttributeService;

class AttributeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerConfig();

        $this->app->singleton(AttributeService::class, fn (Application $app) => new AttributeService(
            $app->make(AttributeRepository::class)
        ));
    }

    /**
     * Register configuration.
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/attribute_types.php', 'attribute_types');
    }
}
