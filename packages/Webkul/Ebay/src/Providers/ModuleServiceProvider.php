<?php

namespace Webkul\Ebay\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Ebay\Adapters\EbayAdapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Ebay\Models\EbayCredentialsConfig::class,
        \Webkul\Ebay\Models\EbayExportMappingConfig::class,
        \Webkul\Ebay\Models\EbayMappingConfig::class,
        \Webkul\Ebay\Models\EbayProductMapping::class,
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
            $resolver->register('ebay', EbayAdapter::class);
        });
    }
}
