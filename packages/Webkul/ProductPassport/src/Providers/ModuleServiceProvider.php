<?php

namespace Webkul\ProductPassport\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * No package-owned models yet: Task 9's seeder writes rows through the
     * existing Attribute package's models, and this task registers the `dpp`
     * publication type only.
     */
    protected $models = [];
}
