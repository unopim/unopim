<?php

namespace Webkul\WooCommerce\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\WooCommerce\Contracts\WooCommerceCredentialsConfig;

class WooCommerceCredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return WooCommerceCredentialsConfig::class;
    }
}
