<?php

namespace Webkul\MagicAI\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        MagicPrompt::class,
        MagicAISystemPrompt::class,
        MagicAIPlatform::class,
    ];
}
