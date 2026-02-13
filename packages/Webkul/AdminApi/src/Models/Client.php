<?php

namespace Webkul\AdminApi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passport\Client as PassportClient;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\User\Models\AdminProxy;

class Client extends PassportClient
{
    use BelongsToTenant, HasUuids;

    public $incrementing = true;

    protected $keyType = 'string';

    /**
     * Get the admins.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admins()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'user_id');
    }
}
