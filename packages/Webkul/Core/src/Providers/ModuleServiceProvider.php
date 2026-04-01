<?php

namespace Webkul\Core\Providers;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Channel::class,
        CoreConfig::class,
        Currency::class,
        Locale::class,
    ];
}
