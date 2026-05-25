<?php

namespace Webkul\Notification\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Notification\Models\Notification;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Notification::class,
    ];
}
