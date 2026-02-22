<?php

namespace Webkul\Order\Services;

use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;
use Webkul\Order\ValueObjects\ChannelProfitability;
use Webkul\Order\ValueObjects\ItemProfitability;
use Webkul\Order\ValueObjects\ProfitabilityResult;
use Webkul\Pricing\Models\ProductCost;

/**
 * ProfitabilityCalculator
 *
 * Calculates profit margins, revenue, and cost basis for orders and order items.
 * Integrates with ProductCost from the Pricing package for accurate cost tracking.
 *
 * @package Webkul\Order\Services
 */
class ProfitabilityCalculator
{
    /**
     * Calculate profitability for a single order.
     *
     * @param  int  $orderId
     * @return ProfitabilityResult
     */
    public function calculateOrderProfitability(int $orderId): ProfitabilityResult
    {
        $order = UnifiedOrder::with(['orderItems.product'])->findOrFail($orderId);

        $totalRevenue = $order->total_amount;
        $totalCost = 0;
        $itemBreakdown = [];

        foreach ($order->orderItems as $item) {
            $itemProfit = $this->calculateItemProfitability($item);
            $totalCost += $itemProfit->costBasis;
            $itemBreakdown[] = $itemProfit;
        }

        $totalProfit = $totalRevenue - $totalCost;
        $marginPercentage = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return new ProfitabilityResult(
            orderId: $order->id,
            orderNumber: $order->order_number,
            revenue: $totalRevenue,
            totalCost: $totalCost,
            profit: $totalProfit,
            marginPercentage: round($marginPercentage, 2),
            itemBreakdown: $itemBreakdown,
            currencyCode: $order->currency_code,
            orderDate: $order->order_date,
            channelId: $order->channel_id
        );
    }

    /**
     * Calculate profitability for an order item.
     *
     * @param  UnifiedOrderItem  $item
     * @return ItemProfitability
     */
    protected function calculateItemProfitability(UnifiedOrderItem $item): ItemProfitability
    {
        $revenue = $item->price * $item->quantity;

        // Get cost basis from ProductCost - find the cost effective at order creation time
        $cost = ProductCost::where('product_id', $item->product_id)
            ->where('effective_from', '<=', $item->created_at)
            ->where(function ($q) use ($item) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $item->created_at);
            })
            ->orderBy('effective_from', 'desc')
            ->first();

        $unitCost = $cost ? $cost->amount : 0;
        $totalCost = $unitCost * $item->quantity;
        $profit = $revenue - $totalCost;
        $marginPercentage = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return new ItemProfitability(
            productId: $item->product_id,
            productName: $item->product_name,
            productSku: $item->product_sku,
            quantity: $item->quantity,
            unitPrice: $item->price,
            revenue: $revenue,
            unitCost: $unitCost,
            costBasis: $totalCost,
            profit: $profit,
            marginPercentage: round($marginPercentage, 2)
        );
    }

    /**
     * Aggregate profitability by channel.
     *
     * @param  int  $channelId
     * @param  array  $dateRange  ['start' => Carbon, 'end' => Carbon]
     * @return ChannelProfitability
     */
    public function aggregateByChannel(int $channelId, array $dateRange = []): ChannelProfitability
    {
        $query = UnifiedOrder::where('channel_id', $channelId);

        if (!empty($dateRange)) {
            $query->whereBetween('order_date', [
                $dateRange['start'] ?? now()->subMonth(),
                $dateRange['end'] ?? now(),
            ]);
        }

        $orders = $query->get();
        $totalRevenue = $orders->sum('total_amount');
        $totalProfit = 0;
        $totalCost = 0;
        $orderBreakdown = [];

        foreach ($orders as $order) {
            $result = $this->calculateOrderProfitability($order->id);
            $totalProfit += $result->profit;
            $totalCost += $result->totalCost;
            $orderBreakdown[] = $result;
        }

        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        $averageOrderValue = $orders->count() > 0 ? $totalRevenue / $orders->count() : 0;

        return new ChannelProfitability(
            channelId: $channelId,
            orderCount: $orders->count(),
            totalRevenue: $totalRevenue,
            totalCost: $totalCost,
            totalProfit: $totalProfit,
            averageOrderValue: round($averageOrderValue, 2),
            profitMargin: round($profitMargin, 2),
            dateRange: $dateRange,
            orderBreakdown: $orderBreakdown
        );
    }

    /**
     * Calculate profitability for multiple orders in batch.
     *
     * @param  array  $orderIds
     * @return array
     */
    public function calculateBatchProfitability(array $orderIds): array
    {
        $results = [];

        foreach ($orderIds as $orderId) {
            try {
                $results[$orderId] = $this->calculateOrderProfitability($orderId);
            } catch (\Exception $e) {
                \Log::error("Failed to calculate profitability for order {$orderId}: {$e->getMessage()}");
                $results[$orderId] = null;
            }
        }

        return $results;
    }

    /**
     * Get top profitable products from orders.
     *
     * @param  array  $filters  ['channel_id' => int, 'date_range' => array, 'limit' => int]
     * @return array
     */
    public function getTopProfitableProducts(array $filters = []): array
    {
        $query = UnifiedOrderItem::query();

        if (isset($filters['channel_id'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('channel_id', $filters['channel_id']);
            });
        }

        if (isset($filters['date_range'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->whereBetween('order_date', [
                    $filters['date_range']['start'] ?? now()->subMonth(),
                    $filters['date_range']['end'] ?? now(),
                ]);
            });
        }

        $items = $query->with('product')->get();
        $productProfits = [];

        foreach ($items as $item) {
            $profitability = $this->calculateItemProfitability($item);
            $productId = $item->product_id;

            if (!isset($productProfits[$productId])) {
                $productProfits[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $profitability->productName,
                    'product_sku' => $profitability->productSku,
                    'total_revenue' => 0,
                    'total_cost' => 0,
                    'total_profit' => 0,
                    'units_sold' => 0,
                ];
            }

            $productProfits[$productId]['total_revenue'] += $profitability->revenue;
            $productProfits[$productId]['total_cost'] += $profitability->costBasis;
            $productProfits[$productId]['total_profit'] += $profitability->profit;
            $productProfits[$productId]['units_sold'] += $profitability->quantity;
        }

        // Calculate margin percentage for each product
        foreach ($productProfits as &$product) {
            $product['margin_percentage'] = $product['total_revenue'] > 0
                ? round(($product['total_profit'] / $product['total_revenue']) * 100, 2)
                : 0;
        }

        // Sort by total profit descending
        usort($productProfits, function ($a, $b) {
            return $b['total_profit'] <=> $a['total_profit'];
        });

        // Apply limit
        $limit = $filters['limit'] ?? 10;

        return array_slice($productProfits, 0, $limit);
    }

    /**
     * Calculate overall profitability summary.
     *
     * @param  array  $filters
     * @return array
     */
    public function getOverallSummary(array $filters = []): array
    {
        $query = UnifiedOrder::query();

        if (isset($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (isset($filters['date_range'])) {
            $query->whereBetween('order_date', [
                $filters['date_range']['start'] ?? now()->subMonth(),
                $filters['date_range']['end'] => now(),
            ]);
        }

        $orders = $query->get();
        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($orders as $order) {
            $result = $this->calculateOrderProfitability($order->id);
            $totalRevenue += $result->revenue;
            $totalCost += $result->totalCost;
            $totalProfit += $result->profit;
        }

        return [
            'order_count' => $orders->count(),
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'average_order_value' => $orders->count() > 0 ? $totalRevenue / $orders->count() : 0,
            'profit_margin_percentage' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0,
            'average_profit_per_order' => $orders->count() > 0 ? $totalProfit / $orders->count() : 0,
        ];
    }
}
