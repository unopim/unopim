<?php

namespace Webkul\Amazon\Providers;

use Webkul\Amazon\Adapters\AmazonAdapter;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class AmazonServiceProvider extends CoreModuleServiceProvider
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
            $resolver->register('amazon', AmazonAdapter::class);
        });
    }
}
