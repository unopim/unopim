<?php

namespace Webkul\Salla\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Salla\Contracts\SallaProductMapping;

class SallaProductMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return SallaProductMapping::class;
    }
}
