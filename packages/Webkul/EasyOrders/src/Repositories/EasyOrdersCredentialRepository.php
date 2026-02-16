<?php

namespace Webkul\EasyOrders\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\EasyOrders\Contracts\EasyOrdersCredentialsConfig;

class EasyOrdersCredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EasyOrdersCredentialsConfig::class;
    }
}
