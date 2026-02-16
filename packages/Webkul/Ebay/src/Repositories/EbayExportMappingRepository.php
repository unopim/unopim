<?php

namespace Webkul\Ebay\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Ebay\Contracts\EbayExportMappingConfig;

class EbayExportMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EbayExportMappingConfig::class;
    }
}
