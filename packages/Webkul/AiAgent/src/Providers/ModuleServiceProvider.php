<?php

namespace Webkul\AiAgent\Providers;

use Webkul\AiAgent\Models\Agent;
use Webkul\AiAgent\Models\AgentExecution;
use Webkul\AiAgent\Models\Credential;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models to register their repository bindings.
     *
     * @var array
     */
    protected $models = [
        Credential::class,
        Agent::class,
        AgentExecution::class,
    ];
}
