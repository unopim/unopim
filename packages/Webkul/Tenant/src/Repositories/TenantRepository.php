<?php

namespace Webkul\Tenant\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Tenant\Contracts\Tenant;

class TenantRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Tenant::class;
    }
}
