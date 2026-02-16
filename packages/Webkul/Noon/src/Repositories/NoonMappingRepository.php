<?php

namespace Webkul\Noon\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Noon\Contracts\NoonMappingConfig;

class NoonMappingRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return NoonMappingConfig::class;
    }
}
