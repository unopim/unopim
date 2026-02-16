<?php

namespace Webkul\EasyOrders\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\EasyOrders\Contracts\EasyOrdersMappingConfig;

class EasyOrdersMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EasyOrdersMappingConfig::class;
    }
}
