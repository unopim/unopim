<?php

namespace Webkul\WooCommerce\Providers;

use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\WooCommerce\Adapters\WooCommerceAdapter;

class WooCommerceServiceProvider extends CoreModuleServiceProvider
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
            $resolver->register('woocommerce', WooCommerceAdapter::class);
        });
    }
}
