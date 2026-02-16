<?php

namespace Webkul\Salla\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Salla\Adapters\SallaAdapter;

class SallaServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [];

    public function boot(): void
    {
        parent::boot();

        $this->registerAdapter();
    }

    protected function registerAdapter(): void
    {
        $this->app->resolving(AdapterResolver::class, function (AdapterResolver $resolver) {
            $resolver->register('salla', SallaAdapter::class);
        });
    }
}
