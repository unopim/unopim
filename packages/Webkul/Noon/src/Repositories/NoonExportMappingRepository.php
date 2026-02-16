<?php

namespace Webkul\Noon\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Noon\Contracts\NoonExportMappingConfig;

class NoonExportMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return NoonExportMappingConfig::class;
    }
}
