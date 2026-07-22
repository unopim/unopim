<?php

namespace Webkul\Publication\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Publication::class,
        PublicationVersion::class,
    ];
}
