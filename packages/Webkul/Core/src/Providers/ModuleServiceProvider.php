<?php

namespace Webkul\Core\Providers;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Core\Models\Channel::class,
        \Webkul\Core\Models\CoreConfig::class,
        \Webkul\Core\Models\Currency::class,
        \Webkul\Core\Models\Locale::class,
    ];
}
