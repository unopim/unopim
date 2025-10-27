<?php

namespace Webkul\Completeness\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Completeness\Models\CompletenessSetting::class,
    ];
}
