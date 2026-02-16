<?php

namespace Webkul\Magento2\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Magento2\Contracts\Magento2MappingConfig as Magento2MappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Magento2MappingConfig extends Model implements Magento2MappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_magento2_data_mapping';

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
