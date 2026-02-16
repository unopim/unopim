<?php

namespace Webkul\WooCommerce\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\WooCommerce\Contracts\WooCommerceMappingConfig;

class WooCommerceMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return WooCommerceMappingConfig::class;
    }
}
