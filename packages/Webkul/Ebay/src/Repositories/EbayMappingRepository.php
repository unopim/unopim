<?php

namespace Webkul\Ebay\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Ebay\Contracts\EbayMappingConfig;

class EbayMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EbayMappingConfig::class;
    }
}
