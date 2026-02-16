<?php

namespace Webkul\Noon\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Noon\Adapters\NoonAdapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Noon\Models\NoonCredentialsConfig::class,
        \Webkul\Noon\Models\NoonExportMappingConfig::class,
        \Webkul\Noon\Models\NoonMappingConfig::class,
        \Webkul\Noon\Models\NoonProductMapping::class,
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
            $resolver->register('noon', NoonAdapter::class);
        });
    }
}
