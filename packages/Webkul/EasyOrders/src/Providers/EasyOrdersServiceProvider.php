<?php

namespace Webkul\EasyOrders\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\EasyOrders\Adapters\EasyOrdersAdapter;

class EasyOrdersServiceProvider extends CoreModuleServiceProvider
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
            $resolver->register('easy_orders', EasyOrdersAdapter::class);
        });
    }
}
