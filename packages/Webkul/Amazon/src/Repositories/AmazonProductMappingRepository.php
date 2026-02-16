<?php

namespace Webkul\Amazon\Repositories;

use Webkul\Amazon\Contracts\AmazonProductMapping;
use Webkul\Core\Eloquent\Repository;

class AmazonProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AmazonProductMapping::class;
    }
}
