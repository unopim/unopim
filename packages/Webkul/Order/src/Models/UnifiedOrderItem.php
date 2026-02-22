<?php

namespace Webkul\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Order\Contracts\UnifiedOrderItem as UnifiedOrderItemContract;

class UnifiedOrderItem extends Model implements UnifiedOrderItemContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unified_order_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'name',
        'quantity',
        'price',
        'total',
        'item_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'item_data' => 'array',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(UnifiedOrder::class, 'order_id');
    }
}
