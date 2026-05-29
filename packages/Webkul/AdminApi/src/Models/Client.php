<?php

namespace Webkul\AdminApi\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Client as PassportClient;
use Webkul\User\Models\AdminProxy;

class Client extends PassportClient
{
    /**
     * Get the admins.
     */
    public function admins(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'user_id');
    }
}
