<?php

namespace Webkul\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Salla\Contracts\SallaMappingConfig as SallaMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class SallaMappingConfig extends Model implements SallaMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_salla_data_mapping';

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
