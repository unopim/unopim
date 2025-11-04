<?php

namespace Webkul\AdminApi\Repositories;

use Webkul\AdminApi\Models\Apikey;
use Webkul\Core\Eloquent\Repository;

class ApiKeyRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Apikey::class;
    }
}
