<?php

namespace Webkul\Amazon\Providers;

use Webkul\Amazon\Adapters\AmazonAdapter;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Amazon\Models\AmazonCredentialsConfig::class,
        \Webkul\Amazon\Models\AmazonExportMappingConfig::class,
        \Webkul\Amazon\Models\AmazonMappingConfig::class,
        \Webkul\Amazon\Models\AmazonProductMapping::class,
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
            $resolver->register('amazon', AmazonAdapter::class);
        });
    }
}
