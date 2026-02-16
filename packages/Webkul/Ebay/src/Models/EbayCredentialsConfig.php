<?php

namespace Webkul\Ebay\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Ebay\Contracts\EbayCredentialsConfig as EbayCredentialsConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class EbayCredentialsConfig extends Model implements EbayCredentialsConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_ebay_credentials_config';

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
