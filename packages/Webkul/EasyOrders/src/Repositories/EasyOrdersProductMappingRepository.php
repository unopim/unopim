<?php

namespace Webkul\EasyOrders\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\EasyOrders\Contracts\EasyOrdersProductMapping;

class EasyOrdersProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EasyOrdersProductMapping::class;
    }
}
