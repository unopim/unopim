<?php

namespace Webkul\DataTransfer\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\DataTransfer\Models\JobInstances::class,
        \Webkul\DataTransfer\Models\JobTrackBatch::class,
        \Webkul\DataTransfer\Models\JobTrack::class,
    ];
}
