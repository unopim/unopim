<?php

namespace Webkul\EasyOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\EasyOrders\Contracts\EasyOrdersExportMappingConfig as EasyOrdersExportMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class EasyOrdersExportMappingConfig extends Model implements EasyOrdersExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_easyorders_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
