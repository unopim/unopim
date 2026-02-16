<?php

namespace Webkul\Magento2\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Magento2\Contracts\Magento2ProductMapping;

class Magento2ProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Magento2ProductMapping::class;
    }
}
