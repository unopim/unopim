<?php

namespace Webkul\WooCommerce\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\WooCommerce\Contracts\WooCommerceExportMappingConfig;

class WooCommerceExportMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return WooCommerceExportMappingConfig::class;
    }
}
