<?php

namespace Webkul\Magento2\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Magento2\Contracts\Magento2ExportMappingConfig as Magento2ExportMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Magento2ExportMappingConfig extends Model implements Magento2ExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_magento2_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
