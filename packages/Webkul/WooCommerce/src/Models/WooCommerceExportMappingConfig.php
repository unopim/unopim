<?php

namespace Webkul\WooCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\WooCommerce\Contracts\WooCommerceExportMappingConfig as WooCommerceExportMappingConfigContract;

class WooCommerceExportMappingConfig extends Model implements WooCommerceExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_woocommerce_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
