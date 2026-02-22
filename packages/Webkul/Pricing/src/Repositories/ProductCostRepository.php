<?php

namespace Webkul\Pricing\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class ProductCostRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\Pricing\Contracts\ProductCost::class;
    }

    /**
     * Get all active (currently effective) costs for a product.
     */
    public function getActiveCostsForProduct(int $productId): Collection
    {
        $today = now()->toDateString();

        return $this->model
            ->where('product_id', $productId)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->with(['product', 'creator'])
            ->get();
    }

    /**
     * Get the total cost amount for a product filtered by cost type.
     */
    public function getTotalCostByType(int $productId, string $costType): float
    {
        $today = now()->toDateString();

        return (float) $this->model
            ->where('product_id', $productId)
            ->where('cost_type', $costType)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->sum('amount');
    }
}
