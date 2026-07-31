<?php

namespace Webkul\ProductPassport\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\ProductPassport\Models\PassportTemplate;
use Webkul\ProductPassport\Models\PassportTemplateFamily;
use Webkul\ProductPassport\Models\PassportTemplateField;
use Webkul\ProductPassport\Models\PassportTemplateFieldTranslation;
use Webkul\ProductPassport\Models\PassportTemplateSection;
use Webkul\ProductPassport\Models\PassportTemplateSectionTranslation;
use Webkul\ProductPassport\Models\PassportTemplateTranslation;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        PassportTemplate::class,
        PassportTemplateTranslation::class,
        PassportTemplateFamily::class,
        PassportTemplateSection::class,
        PassportTemplateSectionTranslation::class,
        PassportTemplateField::class,
        PassportTemplateFieldTranslation::class,
    ];
}
