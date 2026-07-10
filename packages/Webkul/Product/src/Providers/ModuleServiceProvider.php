<?php

namespace Webkul\Product\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\AssociationTypeField;
use Webkul\Product\Models\AssociationTypeFieldOption;
use Webkul\Product\Models\AssociationTypeFieldOptionTranslation;
use Webkul\Product\Models\AssociationTypeFieldTranslation;
use Webkul\Product\Models\AssociationTypeTranslation;
use Webkul\Product\Models\Product;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Product::class,
        AssociationType::class,
        AssociationTypeTranslation::class,
        AssociationTypeField::class,
        AssociationTypeFieldTranslation::class,
        AssociationTypeFieldOption::class,
        AssociationTypeFieldOptionTranslation::class,
    ];
}
