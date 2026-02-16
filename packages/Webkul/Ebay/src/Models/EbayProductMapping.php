<?php

namespace Webkul\Ebay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Ebay\Contracts\EbayProductMapping as EbayProductMappingContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class EbayProductMapping extends Model implements EbayProductMappingContract
{
    use BelongsToTenant;

    protected $table = 'ebay_product_mappings';

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
