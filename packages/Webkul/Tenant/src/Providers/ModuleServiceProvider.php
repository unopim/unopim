<?php

namespace Webkul\Tenant\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Tenant\Models\Tenant::class,
        \Webkul\Tenant\Models\TenantOAuthClient::class,
    ];
}
