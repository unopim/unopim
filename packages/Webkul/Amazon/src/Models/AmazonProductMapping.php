<?php

namespace Webkul\Amazon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Amazon\Contracts\AmazonProductMapping as AmazonProductMappingContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class AmazonProductMapping extends Model implements AmazonProductMappingContract
{
    use BelongsToTenant;

    protected $table = 'amazon_product_mappings';

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
