<?php

namespace Webkul\HistoryControl\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\HistoryControl\Models\History;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        History::class,
    ];
}
