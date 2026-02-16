<?php

namespace Webkul\Noon\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Noon\Contracts\NoonCredentialsConfig;

class NoonCredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return NoonCredentialsConfig::class;
    }
}
