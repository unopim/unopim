<?php

namespace Webkul\Noon\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Noon\Contracts\NoonProductMapping;

class NoonProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return NoonProductMapping::class;
    }
}
