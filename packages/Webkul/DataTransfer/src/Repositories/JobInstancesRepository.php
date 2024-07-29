<?php

namespace Webkul\DataTransfer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobInstances;

class JobInstancesRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return JobInstances::class;
    }
}
