<?php

namespace Webkul\Amazon\Repositories;

use Webkul\Amazon\Contracts\AmazonMappingConfig;
use Webkul\Core\Eloquent\Repository;

class AmazonMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AmazonMappingConfig::class;
    }
}
