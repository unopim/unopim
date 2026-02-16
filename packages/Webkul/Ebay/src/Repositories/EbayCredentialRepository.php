<?php

namespace Webkul\Ebay\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Ebay\Contracts\EbayCredentialsConfig;

class EbayCredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return EbayCredentialsConfig::class;
    }
}
