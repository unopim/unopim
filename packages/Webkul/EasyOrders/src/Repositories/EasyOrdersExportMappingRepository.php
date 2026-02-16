<?php

namespace Webkul\EasyOrders\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\EasyOrders\Contracts\EasyOrdersExportMappingConfig;

class EasyOrdersExportMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EasyOrdersExportMappingConfig::class;
    }
}
