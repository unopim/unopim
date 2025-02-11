<?php

namespace Webkul\Product\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Webkul\Product\ElasticSearch\Filter\BooleanFilter;
use Webkul\Product\ElasticSearch\Filter\DateFilter;
use Webkul\Product\ElasticSearch\Filter\Field\DateTimeFilter;
use Webkul\Product\ElasticSearch\Filter\Field\FamilyFilter;
use Webkul\Product\ElasticSearch\Filter\Field\IdFilter;
use Webkul\Product\ElasticSearch\Filter\Field\ParentFilter;
use Webkul\Product\ElasticSearch\Filter\Field\SkuFilter;
use Webkul\Product\ElasticSearch\Filter\Field\StatusFilter;
use Webkul\Product\ElasticSearch\Filter\Field\TypeFilter;
use Webkul\Product\ElasticSearch\Filter\PriceFilter;
use Webkul\Product\ElasticSearch\Filter\TextFilter;
use Webkul\Product\Facades\ProductImage as ProductImageFacade;
use Webkul\Product\Facades\ProductVideo as ProductVideoFacade;
use Webkul\Product\Facades\ValueSetter as ProductValueSetter;
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
            TextFilter::class,
            BooleanFilter::class,
            DateFilter::class,
            PriceFilter::class,
        ], 'attribute.filters');

        // Register elasticSearch product fields filters
        $this->app->tag([
            TypeFilter::class,
            StatusFilter::class,
            IdFilter::class,
            FamilyFilter::class,
            SkuFilter::class,
            DateTimeFilter::class,
            ParentFilter::class,
        ], 'product.field.filters');
    }
}
