<?php

namespace Webkul\Shopify\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Shopify\Adapters\ShopifyAdapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Shopify\Models\ShopifyCredentialsConfig::class,
        \Webkul\Shopify\Models\ShopifyExportMappingConfig::class,
        \Webkul\Shopify\Models\ShopifyMappingConfig::class,
        \Webkul\Shopify\Models\ShopifyMetaFieldsConfig::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->registerAdapter();
    }

    protected function registerAdapter(): void
    {
        $this->app->resolving(AdapterResolver::class, function (AdapterResolver $resolver) {
            $resolver->register('shopify', ShopifyAdapter::class);
        });
    }
}
