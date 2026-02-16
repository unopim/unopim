<?php

namespace Webkul\Magento2\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Magento2\Contracts\Magento2CredentialsConfig as Magento2CredentialsConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Magento2CredentialsConfig extends Model implements Magento2CredentialsConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_magento2_credentials_config';

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
