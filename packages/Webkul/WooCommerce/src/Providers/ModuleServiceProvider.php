<?php

namespace Webkul\WooCommerce\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\WooCommerce\Adapters\WooCommerceAdapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\WooCommerce\Models\WooCommerceCredentialsConfig::class,
        \Webkul\WooCommerce\Models\WooCommerceExportMappingConfig::class,
        \Webkul\WooCommerce\Models\WooCommerceMappingConfig::class,
        \Webkul\WooCommerce\Models\WooCommerceProductMapping::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migration');
        $this->registerAdapter();
    }

    protected function registerAdapter(): void
    {
        $this->app->resolving(AdapterResolver::class, function (AdapterResolver $resolver) {
            $resolver->register('woocommerce', WooCommerceAdapter::class);
        });
    }
}
