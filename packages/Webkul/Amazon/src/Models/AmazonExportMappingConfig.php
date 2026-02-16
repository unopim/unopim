<?php

namespace Webkul\Amazon\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Amazon\Contracts\AmazonExportMappingConfig as AmazonExportMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class AmazonExportMappingConfig extends Model implements AmazonExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_amazon_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
