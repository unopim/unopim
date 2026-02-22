<?php

namespace Webkul\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\Channel;
use Webkul\Pricing\Contracts\MarginProtectionEvent as MarginProtectionEventContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\User\Models\Admin;

class MarginProtectionEvent extends Model implements MarginProtectionEventContract
{
    use BelongsToTenant, HasFactory;

    protected $table = 'margin_protection_events';

    protected $fillable = [
        'product_id',
        'channel_id',
        'event_type',
        'proposed_price',
        'break_even_price',
        'minimum_margin_price',
        'target_margin_price',
        'currency_code',
        'margin_percentage',
        'minimum_margin_percentage',
        'reason',
        'approved_by',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'proposed_price'            => 'decimal:4',
        'break_even_price'          => 'decimal:4',
        'minimum_margin_price'      => 'decimal:4',
        'target_margin_price'       => 'decimal:4',
        'margin_percentage'         => 'decimal:2',
        'minimum_margin_percentage' => 'decimal:2',
        'approved_at'               => 'datetime',
        'expires_at'                => 'datetime',
    ];

    /**
     * Get the product associated with this event.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the channel associated with this event (nullable).
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Get the admin who approved this event.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Scope to pending events (blocked, not yet approved, not expired).
     */
    public function scopePending($query)
    {
        return $query->where('event_type', 'blocked')
            ->whereNull('approved_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to expired events (past expiration, not yet marked as expired).
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('event_type', '!=', 'expired');
    }

    /**
     * Check if this event has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if this event is pending approval.
     */
    public function isPending(): bool
    {
        return $this->event_type === 'blocked'
            && $this->approved_at === null
            && ! $this->isExpired();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Webkul\Pricing\Database\Factories\MarginProtectionEventFactory::new();
    }
}
