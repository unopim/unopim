<?php

namespace Webkul\Ebay\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Ebay\Contracts\EbayProductMapping;

class EbayProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EbayProductMapping::class;
    }
}
