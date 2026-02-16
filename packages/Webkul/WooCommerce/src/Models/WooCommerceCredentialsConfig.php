<?php

namespace Webkul\WooCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\WooCommerce\Contracts\WooCommerceCredentialsConfig as WooCommerceCredentialsConfigContract;

class WooCommerceCredentialsConfig extends Model implements WooCommerceCredentialsConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_woocommerce_credentials_config';

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
