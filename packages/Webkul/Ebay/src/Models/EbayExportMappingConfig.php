<?php

namespace Webkul\Ebay\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Ebay\Contracts\EbayExportMappingConfig as EbayExportMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class EbayExportMappingConfig extends Model implements EbayExportMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_ebay_export_mapping';

    protected $fillable = [
        'code',
        'external_field',
        'job_instance_id',
    ];
}
