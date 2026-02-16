<?php

namespace Webkul\Magento2\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Magento2\Adapters\Magento2Adapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Magento2\Models\Magento2CredentialsConfig::class,
        \Webkul\Magento2\Models\Magento2ExportMappingConfig::class,
        \Webkul\Magento2\Models\Magento2MappingConfig::class,
        \Webkul\Magento2\Models\Magento2ProductMapping::class,
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
            $resolver->register('magento2', Magento2Adapter::class);
        });
    }
}
