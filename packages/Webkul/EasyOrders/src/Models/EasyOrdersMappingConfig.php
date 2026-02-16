<?php

namespace Webkul\EasyOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\EasyOrders\Contracts\EasyOrdersMappingConfig as EasyOrdersMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class EasyOrdersMappingConfig extends Model implements EasyOrdersMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_easyorders_data_mapping';

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
