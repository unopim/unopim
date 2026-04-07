<?php

namespace Webkul\Completeness\Providers;

use Webkul\Completeness\Models\CompletenessSetting;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        CompletenessSetting::class,
    ];
}
