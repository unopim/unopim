<?php

namespace Webkul\Product\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Product\Models\Product;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Product::class,
    ];
}
