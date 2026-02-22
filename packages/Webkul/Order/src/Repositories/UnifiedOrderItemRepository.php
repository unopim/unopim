<?php

namespace Webkul\Order\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class UnifiedOrderItemRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Order\Contracts\UnifiedOrderItem';
    }

    /**
     * Get items by order.
     */
    public function getItemsByOrder(int $orderId): Collection
    {
        return $this->model
            ->where('unified_order_id', $orderId)
            ->with(['product'])
            ->get();
    }

    /**
     * Calculate item profitability.
     */
    public function calculateItemProfitability(int $itemId): array
    {
        $item = $this->find($itemId);

        if (! $item) {
            return [
                'profit' => 0,
                'margin' => 0,
            ];
        }

        $profit = $item->calculateProfit();
        $revenue = $item->unit_price * $item->quantity;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'profit' => $profit,
            'margin' => round($margin, 2),
        ];
    }

    /**
     * Get top selling products.
     */
    public function getTopSellingProducts(int $limit = 10): Collection
    {
        return $this->model
            ->selectRaw('product_id, product_name, SUM(quantity) as total_quantity, SUM(line_total) as total_revenue, COUNT(*) as order_count')
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }
}
