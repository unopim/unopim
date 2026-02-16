<?php

namespace Webkul\EasyOrders\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\EasyOrders\Adapters\EasyOrdersAdapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\EasyOrders\Models\EasyOrdersCredentialsConfig::class,
        \Webkul\EasyOrders\Models\EasyOrdersExportMappingConfig::class,
        \Webkul\EasyOrders\Models\EasyOrdersMappingConfig::class,
        \Webkul\EasyOrders\Models\EasyOrdersProductMapping::class,
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
            $resolver->register('easyorders', EasyOrdersAdapter::class);
        });
    }
}
