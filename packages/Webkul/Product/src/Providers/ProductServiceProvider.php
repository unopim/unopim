<?php

namespace Webkul\Product\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\Facades\ProductImage as ProductImageFacade;
use Webkul\Product\Facades\ProductVideo as ProductVideoFacade;
use Webkul\Product\Facades\ValueSetter as ProductValueSetter;
use Webkul\Product\Filter\Database\BooleanFilter as DatabaseBooleanFilter;
use Webkul\Product\Filter\Database\DateFilter as DatabaseDateFilter;
use Webkul\Product\Filter\Database\Field\DateTimeFilter as DatabaseDateTimeFilter;
use Webkul\Product\Filter\Database\Field\FamilyFilter as DatabaseFamilyFilter;
use Webkul\Product\Filter\Database\Field\IdFilter as DatabaseIdFilter;
use Webkul\Product\Filter\Database\Field\ParentFilter as DatabaseParentFilter;
use Webkul\Product\Filter\Database\Field\SkuFilter as DatabaseSkuFilter;
use Webkul\Product\Filter\Database\Field\StatusFilter as DatabaseStatusFilter;
use Webkul\Product\Filter\Database\Field\TypeFilter as DatabaseTypeFilter;
use Webkul\Product\Filter\Database\PriceFilter as DatabasePriceFilter;
use Webkul\Product\Filter\Database\TextFilter as DatabaseTextFilter;
use Webkul\Product\Filter\ElasticSearch\BooleanFilter as ElasticSearchBooleanFilter;
use Webkul\Product\Filter\ElasticSearch\DateFilter as ElasticSearchDateFilter;
use Webkul\Product\Filter\ElasticSearch\DateTimeFilter as ElasticSearchDateTimeAttributeFilter;
use Webkul\Product\Filter\ElasticSearch\Field\DateTimeFilter as ElasticSearchDateTimeFilter;
use Webkul\Product\Filter\ElasticSearch\Field\FamilyFilter as ElasticSearchFamilyFilter;
use Webkul\Product\Filter\ElasticSearch\Field\IdFilter as ElasticSearchIdFilter;
use Webkul\Product\Filter\ElasticSearch\Field\ParentFilter as ElasticSearchParentFilter;
use Webkul\Product\Filter\ElasticSearch\Field\SkuFilter as ElasticSearchSkuFilter;
use Webkul\Product\Filter\ElasticSearch\Field\StatusFilter as ElasticSearchStatusFilter;
use Webkul\Product\Filter\ElasticSearch\Field\TypeFilter as ElasticSearchTypeFilter;
use Webkul\Product\Filter\ElasticSearch\PriceFilter as ElasticSearchPriceFilter;
use Webkul\Product\Filter\ElasticSearch\TextFilter as ElasticSearchTextFilter;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Observers\ProductObserver;
use Webkul\Product\ProductImage;
use Webkul\Product\ProductVideo;
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
        ], 'elasticsearch.attribute.filters');

        // Register elasticSearch product fields filters
        $this->app->tag([
            ElasticSearchTypeFilter::class,
            ElasticSearchStatusFilter::class,
            ElasticSearchIdFilter::class,
            ElasticSearchFamilyFilter::class,
            ElasticSearchSkuFilter::class,
            ElasticSearchDateTimeFilter::class,
            ElasticSearchParentFilter::class,
        ], 'elasticsearch.product.field.filters');

        // Register database attribute type filters
        $this->app->tag([
            DatabaseTextFilter::class,
            DatabaseBooleanFilter::class,
            DatabaseDateFilter::class,
            DatabasePriceFilter::class,
        ], 'database.attribute.filters');

        // Register database product fields filters
        $this->app->tag([
            DatabaseFamilyFilter::class,
            DatabaseIdFilter::class,
            DatabaseSkuFilter::class,
            DatabaseStatusFilter::class,
            DatabaseTypeFilter::class,
            DatabaseParentFilter::class,
            DatabaseDateTimeFilter::class,
        ], 'database.product.field.filters');
    }
}
