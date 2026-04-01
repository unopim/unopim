<?php

namespace Webkul\DataTransfer\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        JobInstances::class,
        JobTrackBatch::class,
        JobTrack::class,
    ];
}
