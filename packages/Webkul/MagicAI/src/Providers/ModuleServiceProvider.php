<?php

namespace Webkul\MagicAI\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\MagicAI\Models\MagicPrompt::class,
        \Webkul\MagicAI\Models\MagicSystemPrompt::class,
    ];
}
