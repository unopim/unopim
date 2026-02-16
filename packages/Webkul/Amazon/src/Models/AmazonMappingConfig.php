<?php

namespace Webkul\Amazon\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Amazon\Contracts\AmazonMappingConfig as AmazonMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class AmazonMappingConfig extends Model implements AmazonMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_amazon_data_mapping';

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
