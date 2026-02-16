<?php

namespace Webkul\Salla\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Salla\Contracts\SallaMappingConfig;

class SallaMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return SallaMappingConfig::class;
    }
}
