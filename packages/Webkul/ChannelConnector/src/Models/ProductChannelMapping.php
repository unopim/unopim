<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\ChannelConnector\Contracts\ProductChannelMapping as ProductChannelMappingContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ProductChannelMapping extends Model implements ProductChannelMappingContract
{
    use BelongsToTenant;

    protected $table = 'product_channel_mappings';

    protected $fillable = [
        'channel_connector_id',
        'product_id',
        'external_id',
        'external_variant_id',
        'entity_type',
        'sync_status',
        'last_synced_at',
        'data_hash',
        'meta',
    ];

    protected $casts = [
        'meta'           => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ChannelConnector::class, 'channel_connector_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
