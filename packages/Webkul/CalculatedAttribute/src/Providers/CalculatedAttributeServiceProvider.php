<?php

namespace Webkul\CalculatedAttribute\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\CalculatedAttribute\Contracts\CategoryPathProvider;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;
use Webkul\CalculatedAttribute\Services\CategoryPathResolver;
use Webkul\CalculatedAttribute\Services\FormulaCompiler;
use Webkul\CalculatedAttribute\Services\FormulaValidator;

class CalculatedAttributeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'calculated_attribute');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'calculated_attribute');

        /*
         * Registers the `calculated` attribute type. Webkul\Attribute\Rules\
         * AttributeTypes validates against the keys of this config, so merging
         * here is what makes the type selectable and savable.
         */
        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php',
            'attribute_types'
        );
    }

    /**
     * Register services.
     *
     * Everything is a singleton because each holds a process-local cache — the
     * attribute snapshot, the category tree and the parsed-expression table —
     * which is exactly the caching the design document relies on in place of
     * materialised values.
     */
    public function register(): void
    {
        $this->app->register(CalculatedAttributeEventServiceProvider::class);

        $this->app->singleton(CategoryPathResolver::class);

        $this->app->singleton(CategoryPathProvider::class, fn ($app) => $app->make(CategoryPathResolver::class));

        $this->app->singleton(FormulaFunctions::class);

        $this->app->singleton(SafeExpressionLanguage::class);

        $this->app->singleton(AttributeRegistry::class);

        $this->app->singleton(FormulaCompiler::class);

        $this->app->singleton(FormulaValidator::class);

        $this->app->singleton(CalculatedValueResolver::class);
    }
}
