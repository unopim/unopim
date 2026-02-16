<?php

namespace Webkul\Magento2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Magento2\Contracts\Magento2ProductMapping as Magento2ProductMappingContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Magento2ProductMapping extends Model implements Magento2ProductMappingContract
{
    use BelongsToTenant;

    protected $table = 'magento2_product_mappings';

    protected $fillable = [
        'product_id',
        'connector_id',
        'external_id',
        'external_sku',
        'external_parent_id',
        'variant_data',
        'sync_status',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'variant_data'   => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
