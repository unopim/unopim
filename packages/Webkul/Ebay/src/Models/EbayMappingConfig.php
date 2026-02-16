<?php

namespace Webkul\Ebay\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Ebay\Contracts\EbayMappingConfig as EbayMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class EbayMappingConfig extends Model implements EbayMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_ebay_data_mapping';

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
