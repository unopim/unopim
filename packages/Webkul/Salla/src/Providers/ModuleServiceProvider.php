<?php

namespace Webkul\Salla\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Salla\Adapters\SallaAdapter;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Salla\Models\SallaCredentialsConfig::class,
        \Webkul\Salla\Models\SallaExportMappingConfig::class,
        \Webkul\Salla\Models\SallaMappingConfig::class,
        \Webkul\Salla\Models\SallaProductMapping::class,
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
            $resolver->register('salla', SallaAdapter::class);
        });
    }
}
