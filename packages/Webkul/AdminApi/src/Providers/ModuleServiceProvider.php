<?php

namespace Webkul\AdminApi\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\AdminApi\Models\Apikey;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Apikey::class,
    ];
}
