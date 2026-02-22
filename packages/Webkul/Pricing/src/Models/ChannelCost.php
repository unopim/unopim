<?php

namespace Webkul\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\Channel;
use Webkul\Pricing\Contracts\ChannelCost as ChannelCostContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ChannelCost extends Model implements ChannelCostContract
{
    use BelongsToTenant, HasFactory;

    protected $table = 'channel_costs';

    protected $fillable = [
        'channel_id',
        'commission_percentage',
        'fixed_fee_per_order',
        'payment_processing_percentage',
        'payment_fixed_fee',
        'shipping_cost_per_zone',
        'currency_code',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'commission_percentage'         => 'decimal:2',
        'fixed_fee_per_order'           => 'decimal:2',
        'payment_processing_percentage' => 'decimal:2',
        'payment_fixed_fee'             => 'decimal:2',
        'shipping_cost_per_zone'        => 'array',
        'effective_from'                => 'date',
        'effective_to'                  => 'date',
    ];

    /**
     * Get the channel that owns this cost structure.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Scope to active cost entries (currently effective).
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            });
    }

    /**
     * Scope to costs for a specific channel.
     */
    public function scopeForChannel($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Webkul\Pricing\Database\Factories\ChannelCostFactory::new();
    }
}
