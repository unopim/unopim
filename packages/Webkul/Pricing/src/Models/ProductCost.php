<?php

namespace Webkul\Pricing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Pricing\Contracts\ProductCost as ProductCostContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\User\Models\Admin;

class ProductCost extends Model implements ProductCostContract
{
    use BelongsToTenant, HasFactory;

    protected $table = 'product_costs';

    protected $fillable = [
        'product_id',
        'cost_type',
        'amount',
        'currency_code',
        'effective_from',
        'effective_to',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:4',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /**
     * Get the product that owns this cost.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the admin who created this cost entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
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
     * Scope to costs for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to costs of a specific type.
     */
    public function scopeOfType($query, string $costType)
    {
        return $query->where('cost_type', $costType);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Webkul\Pricing\Database\Factories\ProductCostFactory::new();
    }
}
