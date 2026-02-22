<?php

namespace Webkul\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Order\Contracts\UnifiedOrder as UnifiedOrderContract;

class UnifiedOrder extends Model implements UnifiedOrderContract
{
    use HistoryTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unified_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'channel_id',
        'channel_type',
        'channel_order_id',
        'customer_name',
        'customer_email',
        'status',
        'total_amount',
        'currency_code',
        'order_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_data' => 'array',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the order items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(UnifiedOrderItem::class, 'order_id');
    }
}
