<?php

namespace Webkul\Measurement\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Attribute\Services\AttributeNormalizerFactory;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Measurement\Console\Commands\RecalculateMeasurementValues;
use Webkul\Measurement\Database\Seeders\MeasurementFamilySeeder;
use Webkul\Measurement\DataGrids\MeasurementProductDataGrid;
use Webkul\Measurement\Filter\Database\MeasurementFilter;
use Webkul\Measurement\Filter\ElasticSearch\MeasurementFilter as MeasurementElasticSearchFilter;
use Webkul\Measurement\Helpers\Exporters\ProductExporter;
use Webkul\Measurement\Http\Controllers\MeasurementProductController;
use Webkul\Measurement\Normalizer\ProductAttributeValuesNormalizer as MeasurementProductAttributeValuesNormalizer;
use Webkul\Measurement\Observers\ProductObserver;
use Webkul\Measurement\Services\Normalizers\MeasurementNormalizer;
use Webkul\Product\Models\Product;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

class MeasurementServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Database/Seeders' => database_path('seeders'),
        ], 'measurement-seeders');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'measurement');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'measurement');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php',
            'attribute_types'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/system_settings.php',
            'system_settings'
        );

        if ($this->app->runningInConsole()) {

            $this->commands([
                RecalculateMeasurementValues::class,
            ]);

            Event::listen(CommandFinished::class, function ($event): void {

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

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(MeasurementEventServiceProvider::class);

        $this->app->extend(AttributeNormalizerFactory::class, function (?object $factory): ?object {
            \Closure::bind(function (): void {
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

        $this->app->bind(
            ProductDataGrid::class,
            MeasurementProductDataGrid::class
        );

        $this->app->bind(
            ProductController::class,
            MeasurementProductController::class
        );

        $this->app->tag(
            [MeasurementFilter::class],
            'unopim.database.attribute.filters'
        );

        $this->app->tag(
            [MeasurementElasticSearchFilter::class],
            'unopim.elasticsearch.attribute.filters'
        );

        $this->replaceConfigRecursivelyFrom(
            dirname(__DIR__).'/Config/product_filter_operators.php',
            'product_filter_operators'
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
