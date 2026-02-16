<?php

namespace Webkul\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Salla\Contracts\SallaProductMapping as SallaProductMappingContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class SallaProductMapping extends Model implements SallaProductMappingContract
{
    use BelongsToTenant;

    protected $table = 'salla_product_mappings';

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
