<?php

namespace Webkul\Noon\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Noon\Contracts\NoonMappingConfig as NoonMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class NoonMappingConfig extends Model implements NoonMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_noon_data_mapping';

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
