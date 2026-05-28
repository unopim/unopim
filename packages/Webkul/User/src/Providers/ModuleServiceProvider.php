<?php

namespace Webkul\User\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Admin::class,
        Role::class,
    ];
}
