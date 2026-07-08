<?php

namespace Webkul\Measurement\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Services\AttributeNormalizerFactory;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Measurement\Database\Seeders\MeasurementFamilySeeder;
use Webkul\Measurement\DataGrids\MeasurementProductDataGrid;
use Webkul\Measurement\Filter\Database\MeasurementFilter;
use Webkul\Measurement\Helpers\Exporters\ProductExporter;
use Webkul\Measurement\Normalizer\ProductAttributeValuesNormalizer as MeasurementProductAttributeValuesNormalizer;
use Webkul\Measurement\Observers\ProductObserver;
use Webkul\Measurement\Services\Normalizers\MeasurementNormalizer;
use Webkul\Product\Models\Product;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

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

        /**
         * Override the core datagrid filters view so the measurement attribute
         * gets its dedicated two-field (value + unit) filter. Prepended so this
         * package's copy wins for `admin::components.datagrid.filters` while every
         * other `admin::` view still falls through to the core package.
         */
        $this->app['view']->prependNamespace(
            'admin',
            __DIR__.'/../Resources/views/admin-override'
        );
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php',
            'attribute_types'
        );

        if ($this->app->runningInConsole()) {

            Event::listen(CommandFinished::class, function ($event) {

                if (
                    $event->command === 'unopim:install'
                    && class_exists(MeasurementFamilySeeder::class)
                ) {

                    Artisan::call('db:seed', [
                        '--class' => MeasurementFamilySeeder::class,
                    ]);
                }
            });
        }

        Product::observe(ProductObserver::class);
    }

    public function register()
    {
        $this->app->register(MeasurementEventServiceProvider::class);

        $this->app->extend(AttributeNormalizerFactory::class, function ($factory) {
            \Closure::bind(function () {
                $this->normalizers['measurement'] = MeasurementNormalizer::class;
            }, $factory, AttributeNormalizerFactory::class)();

            return $factory;
        });

        $this->app->bind(
            FieldProcessor::class,
            \Webkul\Measurement\Helpers\Importers\FieldProcessor::class
        );

        $this->app->bind(
            Exporter::class,
            ProductExporter::class
        );

        $this->app->bind(
            ProductAttributeValuesNormalizer::class,
            MeasurementProductAttributeValuesNormalizer::class
        );

        $this->app->bind(
            Importer::class,
            \Webkul\Measurement\Helpers\Importers\Product\Importer::class
        );

        /**
         * Reuse the price-style two-field filter UI for measurement attributes
         * in the product datagrid (value input + unit dropdown).
         */
        $this->app->bind(
            ProductDataGrid::class,
            MeasurementProductDataGrid::class
        );

        /**
         * Register the database filter that matches measurement attributes by
         * unit + amount. Tagged so the FilterManager picks it up for the
         * 'measurement' attribute type.
         */
        $this->app->tag(
            [MeasurementFilter::class],
            'unopim.database.attribute.filters'
        );

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php', 'acl'
        );
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/api-acl.php', 'api-acl'
        );
    }
}
