<?php

namespace Webkul\CalculatedAttribute\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\Attribute\Contracts\DerivedValueResolver as DerivedValueResolverContract;
use Webkul\CalculatedAttribute\Contracts\CategoryPathProvider;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;
use Webkul\CalculatedAttribute\Services\CategoryPathResolver;
use Webkul\CalculatedAttribute\Services\FormulaCompiler;
use Webkul\CalculatedAttribute\Services\FormulaValidator;
use Webkul\CalculatedAttribute\View\Composers\CalculatedFieldValues;
use Webkul\CalculatedAttribute\View\Composers\FormulaReference;

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

        $this->registerConfig();

        View::composer('admin::catalog.products.edit.attribute-group-panel', CalculatedFieldValues::class);

        View::composer('calculated_attribute::catalog.attributes.formula', FormulaReference::class);
    }

    /**
     * Register the `calculated` attribute type.
     *
     * Webkul\Attribute\Rules\AttributeTypes validates against the keys of this
     * config, so merging here is what makes the type selectable and savable.
     * The entry also marks itself not fillable, which is how the product
     * importer and the completeness settings learn to leave it alone without
     * naming it.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php',
            'attribute_types'
        );
    }

    /**
     * Register services.
     *
     * Everything is `scoped()` rather than a singleton: each holds a
     * process-local cache — the attribute snapshot, the categories read so far,
     * the parsed-expression table — and a singleton would carry all three
     * across requests under Octane and across jobs in a queue worker, serving
     * an attribute list or a category name that changed hours ago. A scoped
     * instance is discarded between units of work, so the caching still pays
     * for itself within one page or one export batch without ever going stale.
     */
    public function register(): void
    {
        $this->app->register(CalculatedAttributeEventServiceProvider::class);

        $this->app->scoped(CategoryPathResolver::class);

        $this->app->scoped(
            CategoryPathProvider::class,
            fn ($app): CategoryPathResolver => $app->make(CategoryPathResolver::class)
        );

        $this->app->scoped(FormulaFunctions::class);

        $this->app->scoped(SafeExpressionLanguage::class);

        $this->app->scoped(AttributeRegistry::class);

        $this->app->scoped(FormulaCompiler::class);

        $this->app->scoped(FormulaValidator::class);

        $this->app->scoped(CalculatedValueResolver::class);

        $this->app->bind(
            DerivedValueResolverContract::class,
            fn ($app): CalculatedValueResolver => $app->make(CalculatedValueResolver::class)
        );
    }
}
