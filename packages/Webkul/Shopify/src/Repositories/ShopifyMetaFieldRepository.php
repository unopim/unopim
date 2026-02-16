<?php

namespace Webkul\Shopify\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Shopify\Contracts\ShopifyMetaFieldsConfig;

class ShopifyMetaFieldRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ShopifyMetaFieldsConfig::class;
    }
}
