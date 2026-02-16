<?php

namespace Webkul\Salla\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Salla\Contracts\SallaExportMappingConfig;

class SallaExportMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return SallaExportMappingConfig::class;
    }
}
