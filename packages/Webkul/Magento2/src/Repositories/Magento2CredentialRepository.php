<?php

namespace Webkul\Magento2\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Magento2\Contracts\Magento2CredentialsConfig;

class Magento2CredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Magento2CredentialsConfig::class;
    }
}
