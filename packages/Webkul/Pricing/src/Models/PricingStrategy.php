<?php

namespace Webkul\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\Channel;
use Webkul\Pricing\Contracts\PricingStrategy as PricingStrategyContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class PricingStrategy extends Model implements PricingStrategyContract
{
    use BelongsToTenant, HasFactory;

    protected $table = 'pricing_strategies';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'minimum_margin_percentage',
        'target_margin_percentage',
        'premium_margin_percentage',
        'psychological_pricing',
        'round_to',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'minimum_margin_percentage' => 'decimal:2',
        'target_margin_percentage'  => 'decimal:2',
        'premium_margin_percentage' => 'decimal:2',
        'psychological_pricing'     => 'boolean',
        'is_active'                 => 'boolean',
    ];

    /**
     * Scope to active strategies only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to strategies matching a specific scope type and optional scope ID.
     */
    public function scopeForScope($query, string $scopeType, ?int $scopeId = null)
    {
        $query->where('scope_type', $scopeType);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }

        return $query;
    }

    /**
     * Scope to global strategies.
     */
    public function scopeGlobal($query)
    {
        return $query->where('scope_type', 'global');
    }

    /**
     * Resolve the most specific active pricing strategy for a product.
     *
     * Resolution order (most specific wins): product > channel > category > global.
     * Within same scope type, highest priority wins.
     */
    public static function resolveForProduct(Product $product, ?Channel $channel = null): self
    {
        // 1. Product-specific strategy
        $strategy = static::active()
            ->where('scope_type', 'product')
            ->where('scope_id', $product->id)
            ->orderByDesc('priority')
            ->first();

        if ($strategy) {
            return $strategy;
        }

        // 2. Channel-specific strategy
        if ($channel) {
            $strategy = static::active()
                ->where('scope_type', 'channel')
                ->where('scope_id', $channel->id)
                ->orderByDesc('priority')
                ->first();

            if ($strategy) {
                return $strategy;
            }
        }

        // 3. Category-specific strategy
        if ($product->attribute_family_id) {
            $strategy = static::active()
                ->where('scope_type', 'category')
                ->where('scope_id', $product->attribute_family_id)
                ->orderByDesc('priority')
                ->first();

            if ($strategy) {
                return $strategy;
            }
        }

        // 4. Global fallback strategy
        $strategy = static::active()
            ->where('scope_type', 'global')
            ->orderByDesc('priority')
            ->first();

        if ($strategy) {
            return $strategy;
        }

        // Return a default strategy if none exists
        return new static([
            'scope_type'                => 'global',
            'minimum_margin_percentage' => 15.00,
            'target_margin_percentage'  => 25.00,
            'premium_margin_percentage' => 40.00,
            'psychological_pricing'     => true,
            'round_to'                  => '0.99',
            'is_active'                 => true,
            'priority'                  => 0,
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Webkul\Pricing\Database\Factories\PricingStrategyFactory::new();
    }
}
