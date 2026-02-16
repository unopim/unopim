<?php

namespace Webkul\Amazon\Repositories;

use Webkul\Amazon\Contracts\AmazonExportMappingConfig;
use Webkul\Core\Eloquent\Repository;

class AmazonExportMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AmazonExportMappingConfig::class;
    }
}
