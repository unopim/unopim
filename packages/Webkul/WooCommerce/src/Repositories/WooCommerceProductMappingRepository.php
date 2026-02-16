<?php

namespace Webkul\WooCommerce\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\WooCommerce\Contracts\WooCommerceProductMapping;

class WooCommerceProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return WooCommerceProductMapping::class;
    }
}
