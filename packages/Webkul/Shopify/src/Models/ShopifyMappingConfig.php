<?php

namespace Webkul\Shopify\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Shopify\Contracts\ShopifyMappingConfig as ShopifyMappingConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ShopifyMappingConfig extends Model implements ShopifyMappingConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_shopify_data_mapping';

    protected $fillable = [
        'entityType',
        'code',
        'externalId',
        'jobInstanceId',
        'relatedId',
        'relatedSource',
        'apiUrl',
    ];
}
