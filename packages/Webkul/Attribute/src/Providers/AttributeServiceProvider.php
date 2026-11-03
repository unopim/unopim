<?php

namespace Webkul\Attribute\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Attribute\Contracts\DerivedValueResolver as DerivedValueResolverContract;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Attribute\Services\NullDerivedValueResolver;

class AttributeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerConfig();

        $this->app->singleton(AttributeService::class, fn ($app): AttributeService => new AttributeService(
            $app->make(AttributeRepository::class)
        ));
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerBindings();
    }

    /**
     * Register overridable service bindings.
     *
     * In register() rather than boot(), so a package that replaces one does so
     * by registering later — binding from boot() would silently win over every
     * package, whatever the order in bootstrap/providers.php says.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(DerivedValueResolverContract::class, NullDerivedValueResolver::class);
    }

    /**
     * Register configuration.
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/attribute_types.php', 'attribute_types');
    }
}
