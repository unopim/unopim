<?php

namespace Webkul\Category\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Category\Models\Category::class,
        \Webkul\Category\Models\CategoryField::class,
        \Webkul\Category\Models\CategoryFieldOption::class,
        \Webkul\Category\Models\CategoryFieldOptionTranslation::class,
        \Webkul\Category\Models\CategoryFieldTranslation::class,

    ];
}
