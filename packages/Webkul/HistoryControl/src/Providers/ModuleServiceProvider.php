<?php

namespace Webkul\HistoryControl\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\HistoryControl\Models\History::class,
    ];
}
