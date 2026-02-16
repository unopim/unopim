<?php

namespace Webkul\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Salla\Contracts\SallaCredentialsConfig as SallaCredentialsConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class SallaCredentialsConfig extends Model implements SallaCredentialsConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_salla_credentials_config';

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
