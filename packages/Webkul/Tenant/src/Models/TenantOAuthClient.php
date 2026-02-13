<?php

namespace Webkul\Tenant\Models;

use Laravel\Passport\Client as PassportClient;
use Webkul\Tenant\Contracts\TenantOAuthClient as TenantOAuthClientContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class TenantOAuthClient extends PassportClient implements TenantOAuthClientContract
{
    use BelongsToTenant;

    protected $table = 'oauth_clients';
}
