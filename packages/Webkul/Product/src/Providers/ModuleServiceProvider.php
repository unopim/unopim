<?php

namespace Webkul\Product\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Product::class,
        VariantStructure::class,
    ];
}
