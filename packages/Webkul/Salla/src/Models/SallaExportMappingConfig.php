<?php

namespace Webkul\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Salla\Contracts\SallaExportMappingConfig as SallaExportMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class SallaExportMappingConfig extends Model implements SallaExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_salla_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
