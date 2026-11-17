<?php

namespace Webkul\Core\Tests\Concerns;

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;

trait InteractsWithPlatformModules
{
    /**
     * Store the platform module selection and return a fresh scoped gate that reads it.
     *
     * @param  string|list<string>  $modules
     */
    public function setPlatformModules(string|array $modules): ModuleGate
    {
        CoreConfig::query()->updateOrCreate(
            ['code' => ModuleGate::CONFIG_KEY, 'channel_code' => null, 'locale_code' => null],
            ['value' => is_array($modules) ? implode(',', $modules) : $modules],
        );

        app()->forgetScopedInstances();

        return app(ModuleGate::class);
    }
}
