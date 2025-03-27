<?php

namespace Webkul\Product\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\Facades\ProductImage as ProductImageFacade;
use Webkul\Product\Facades\ProductVideo as ProductVideoFacade;
use Webkul\Product\Facades\ValueSetter as ProductValueSetter;
use Webkul\Product\Filter\Database\BooleanFilter as DatabaseBooleanFilter;
use Webkul\Product\Filter\Database\DateFilter as DatabaseDateFilter;
use Webkul\Product\Filter\Database\PriceFilter as DatabasePriceFilter;
use Webkul\Product\Filter\Database\Property\DateTimeFilter as DatabaseDateTimeFilter;
use Webkul\Product\Filter\Database\Property\FamilyFilter as DatabaseFamilyFilter;
use Webkul\Product\Filter\Database\Property\IdFilter as DatabaseIdFilter;
use Webkul\Product\Filter\Database\Property\ParentFilter as DatabaseParentFilter;
use Webkul\Product\Filter\Database\Property\SkuFilter as DatabaseSkuFilter;
use Webkul\Product\Filter\Database\Property\StatusFilter as DatabaseStatusFilter;
use Webkul\Product\Filter\Database\Property\TypeFilter as DatabaseTypeFilter;
use Webkul\Product\Filter\Database\TextFilter as DatabaseTextFilter;
use Webkul\Product\Filter\ElasticSearch\BooleanFilter as ElasticSearchBooleanFilter;
use Webkul\Product\Filter\ElasticSearch\DateFilter as ElasticSearchDateFilter;
use Webkul\Product\Filter\ElasticSearch\DateTimeFilter as ElasticSearchDateTimeAttributeFilter;
use Webkul\Product\Filter\ElasticSearch\OptionFilter as ElasticSearchOptionFilter;
use Webkul\Product\Filter\ElasticSearch\PriceFilter as ElasticSearchPriceFilter;
use Webkul\Product\Filter\ElasticSearch\Property\DateTimeFilter as ElasticSearchDateTimeFilter;
use Webkul\Product\Filter\ElasticSearch\Property\FamilyFilter as ElasticSearchFamilyFilter;
use Webkul\Product\Filter\ElasticSearch\Property\IdFilter as ElasticSearchIdFilter;
use Webkul\Product\Filter\ElasticSearch\Property\ParentFilter as ElasticSearchParentFilter;
use Webkul\Product\Filter\ElasticSearch\Property\SkuFilter as ElasticSearchSkuFilter;
use Webkul\Product\Filter\ElasticSearch\Property\StatusFilter as ElasticSearchStatusFilter;
use Webkul\Product\Filter\ElasticSearch\Property\TypeFilter as ElasticSearchTypeFilter;
use Webkul\Product\Filter\ElasticSearch\TextFilter as ElasticSearchTextFilter;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Observers\ProductObserver;
use Webkul\Product\ProductImage;
use Webkul\Product\ProductVideo;
use Webkul\Product\Services\ProductValueMapper;
use Webkul\Product\ValueSetter;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        include __DIR__.'/../Http/helpers.php';

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'product');

        $this->app->register(EventServiceProvider::class);

        ProductProxy::observe(ProductObserver::class);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->registerFacades();

        $this->registerTags();
    }

    /**
     * Register configuration.
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/product_types.php', 'product_types');
    }

    /**
     * Register Bouncer as a singleton.
     */
    protected function registerFacades(): void
    {
        /**
         * Product image.
         */
        $loader = AliasLoader::getInstance();

        $loader->alias('product_image', ProductImageFacade::class);

        $this->app->singleton('product_image', function () {
            return app()->make(ProductImage::class);
        });

        /**
         * Product video.
         */
        $loader->alias('product_video', ProductVideoFacade::class);

        $this->app->singleton('product_video', function () {
            return app()->make(ProductVideo::class);
        });

        /**
         * Product Values setter
         */
        $loader->alias('value_setter', ProductValueSetter::class);

        $this->app->singleton('value_setter', function () {
            return app()->make(ValueSetter::class);
        });

        /**
         * Product value mapper
         */
        $this->app->singleton('product_value_mapper', function ($app) {
            return new ProductValueMapper;
        });
    }

    protected function registerTags(): void
    {
        // Register elasticSearch attribute type filters
        $this->app->tag([
            ElasticSearchTextFilter::class,
            ElasticSearchBooleanFilter::class,
            ElasticSearchDateFilter::class,
            ElasticSearchDateTimeAttributeFilter::class,
            ElasticSearchPriceFilter::class,
            ElasticSearchOptionFilter::class,
        ], 'unopim.elasticsearch.attribute.filters');

        // Register elasticSearch product Properties filters
        $this->app->tag([
            ElasticSearchTypeFilter::class,
            ElasticSearchStatusFilter::class,
            ElasticSearchIdFilter::class,
            ElasticSearchFamilyFilter::class,
            ElasticSearchSkuFilter::class,
            ElasticSearchDateTimeFilter::class,
            ElasticSearchParentFilter::class,
        ], 'unopim.elasticsearch.product.property.filters');

        // Register database attribute type filters
        $this->app->tag([
            DatabaseTextFilter::class,
            DatabaseBooleanFilter::class,
            DatabaseDateFilter::class,
            DatabasePriceFilter::class,
        ], 'unopim.database.attribute.filters');

        // Register database product Properties filters
        $this->app->tag([
            DatabaseFamilyFilter::class,
            DatabaseIdFilter::class,
            DatabaseSkuFilter::class,
            DatabaseStatusFilter::class,
            DatabaseTypeFilter::class,
            DatabaseParentFilter::class,
            DatabaseDateTimeFilter::class,
        ], 'unopim.database.product.property.filters');
    }
}
