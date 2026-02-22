<?php

namespace Webkul\Order\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Order\Models\UnifiedOrder::class,
        \Webkul\Order\Models\UnifiedOrderItem::class,
        \Webkul\Order\Models\OrderSyncLog::class,
        \Webkul\Order\Models\OrderWebhook::class,
    ];
}
