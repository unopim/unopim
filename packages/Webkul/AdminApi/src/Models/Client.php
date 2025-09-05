<?php

namespace Webkul\AdminApi\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client as PassportClient;
use Webkul\User\Models\AdminProxy;

class Client extends PassportClient
{
    use HasUuids;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL → UUID primary key
            $this->incrementing = false;
            $this->keyType = 'string';
        } else {
            // MySQL → auto-increment bigint
            $this->incrementing = true;
            $this->keyType = 'int';
        }
    }

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
