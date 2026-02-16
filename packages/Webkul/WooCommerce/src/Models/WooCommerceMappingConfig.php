<?php

namespace Webkul\WooCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\WooCommerce\Contracts\WooCommerceMappingConfig as WooCommerceMappingConfigContract;

class WooCommerceMappingConfig extends Model implements WooCommerceMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_woocommerce_data_mapping';

    protected $fillable = [
        'entity_type',
        'code',
        'external_id',
        'job_instance_id',
        'related_id',
        'related_source',
        'api_url',
    ];
}
