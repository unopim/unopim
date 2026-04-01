<?php

namespace Webkul\Category\Providers;

use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;
use Webkul\Category\Models\CategoryFieldOptionTranslation;
use Webkul\Category\Models\CategoryFieldTranslation;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Category::class,
        CategoryField::class,
        CategoryFieldOption::class,
        CategoryFieldOptionTranslation::class,
        CategoryFieldTranslation::class,

    ];
}
