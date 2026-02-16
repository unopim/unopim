<?php

namespace Webkul\Magento2\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Magento2\Adapters\Magento2Adapter;

class Magento2ServiceProvider extends CoreModuleServiceProvider
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
            $resolver->register('magento2', Magento2Adapter::class);
        });
    }
}
