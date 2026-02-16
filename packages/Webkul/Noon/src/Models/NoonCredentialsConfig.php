<?php

namespace Webkul\Noon\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Noon\Contracts\NoonCredentialsConfig as NoonCredentialsConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class NoonCredentialsConfig extends Model implements NoonCredentialsConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_noon_credentials_config';

    protected $fillable = [
        'merchant_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'active',
        'store_name',
        'store_locale_mapping',
        'store_locales',
        'default_set',
        'extras',
    ];

    protected $casts = [
        'store_locale_mapping' => 'array',
        'store_locales'        => 'array',
        'extras'               => 'array',
        'active'               => 'boolean',
        'default_set'          => 'boolean',
        'expires_at'           => 'datetime',
    ];
}
