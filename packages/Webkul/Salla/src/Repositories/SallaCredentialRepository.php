<?php

namespace Webkul\Salla\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Salla\Contracts\SallaCredentialsConfig;

class SallaCredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return SallaCredentialsConfig::class;
    }
}
