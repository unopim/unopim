<?php

namespace Webkul\AdminApi\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\AdminApi\Models\Apikey::class,
    ];
}
