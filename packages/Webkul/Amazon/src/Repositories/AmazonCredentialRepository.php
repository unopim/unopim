<?php

namespace Webkul\Amazon\Repositories;

use Webkul\Amazon\Contracts\AmazonCredentialsConfig;
use Webkul\Core\Eloquent\Repository;

class AmazonCredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AmazonCredentialsConfig::class;
    }
}
