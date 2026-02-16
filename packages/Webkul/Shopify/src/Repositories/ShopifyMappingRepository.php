<?php

namespace Webkul\Shopify\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Shopify\Contracts\ShopifyMappingConfig;

class ShopifyMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ShopifyMappingConfig::class;
    }
}
