<?php

namespace Webkul\Pricing\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Pricing\Models\ProductCost::class,
        \Webkul\Pricing\Models\ChannelCost::class,
        \Webkul\Pricing\Models\MarginProtectionEvent::class,
        \Webkul\Pricing\Models\PricingStrategy::class,
    ];
}
