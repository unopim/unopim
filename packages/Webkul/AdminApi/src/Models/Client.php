<?php

namespace Webkul\AdminApi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Client as PassportClient;
use Webkul\User\Models\AdminProxy;

class Client extends PassportClient
{
    use HasUuids;

    /**
     * Get the admins.
     *
     * @return BelongsTo
     */
    public function admins()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'user_id');
    }
}
