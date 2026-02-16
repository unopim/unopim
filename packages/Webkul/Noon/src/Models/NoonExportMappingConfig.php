<?php

namespace Webkul\Noon\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Noon\Contracts\NoonExportMappingConfig as NoonExportMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class NoonExportMappingConfig extends Model implements NoonExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_noon_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
