<?php

namespace Webkul\Order\ValueObjects;

/**
 * ChannelProfitability
 *
 * Value object representing aggregated profitability analysis for a channel.
 * Contains totals, averages, and order-level breakdown for a date range.
 *
 * @package Webkul\Order\ValueObjects
 */
readonly class ChannelProfitability
{
    /**
     * Create a new ChannelProfitability instance.
     *
     * @param  int  $channelId  Channel ID
     * @param  int  $orderCount  Number of orders
     * @param  float  $totalRevenue  Total revenue
     * @param  float  $totalCost  Total cost
     * @param  float  $totalProfit  Total profit
     * @param  float  $averageOrderValue  Average order value
     * @param  float  $profitMargin  Overall profit margin percentage
     * @param  array  $dateRange  Date range for analysis
     * @param  array  $orderBreakdown  Array of ProfitabilityResult objects
     */
    public function __construct(
        public int $channelId,
        public int $orderCount,
        public float $totalRevenue,
        public float $totalCost,
        public float $totalProfit,
        public float $averageOrderValue,
        public float $profitMargin,
        public array $dateRange = [],
        public array $orderBreakdown = []
    ) {}

    /**
     * Check if the channel is profitable.
     *
     * @return bool
     */
    public function isProfitable(): bool
    {
        return $this->totalProfit > 0;
    }

    /**
     * Get average profit per order.
     *
     * @return float
     */
    public function getAverageProfitPerOrder(): float
    {
        return $this->orderCount > 0 ? round($this->totalProfit / $this->orderCount, 2) : 0;
    }

    /**
     * Get average cost per order.
     *
     * @return float
     */
    public function getAverageCostPerOrder(): float
    {
        return $this->orderCount > 0 ? round($this->totalCost / $this->orderCount, 2) : 0;
    }

    /**
     * Get return on investment (ROI) percentage.
     *
     * @return float
     */
    public function getROI(): float
    {
        return $this->totalCost > 0 ? round(($this->totalProfit / $this->totalCost) * 100, 2) : 0;
    }

    /**
     * Get number of profitable orders.
     *
     * @return int
     */
    public function getProfitableOrderCount(): int
    {
        return count(array_filter($this->orderBreakdown, fn ($order) => $order->isProfitable()));
    }

    /**
     * Get profitability rate (percentage of profitable orders).
     *
     * @return float
     */
    public function getProfitabilityRate(): float
    {
        return $this->orderCount > 0
            ? round(($this->getProfitableOrderCount() / $this->orderCount) * 100, 2)
            : 0;
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'channel_id' => $this->channelId,
            'order_count' => $this->orderCount,
            'total_revenue' => $this->totalRevenue,
            'total_cost' => $this->totalCost,
            'total_profit' => $this->totalProfit,
            'average_order_value' => $this->averageOrderValue,
            'average_profit_per_order' => $this->getAverageProfitPerOrder(),
            'average_cost_per_order' => $this->getAverageCostPerOrder(),
            'profit_margin' => $this->profitMargin,
            'roi_percentage' => $this->getROI(),
            'is_profitable' => $this->isProfitable(),
            'profitable_order_count' => $this->getProfitableOrderCount(),
            'profitability_rate' => $this->getProfitabilityRate(),
            'date_range' => $this->dateRange,
            'order_breakdown_count' => count($this->orderBreakdown),
        ];
    }

    /**
     * Convert to JSON representation.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Get summary statistics.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'channel_id' => $this->channelId,
            'period' => $this->dateRange,
            'performance' => [
                'orders' => $this->orderCount,
                'revenue' => $this->totalRevenue,
                'profit' => $this->totalProfit,
                'margin' => $this->profitMargin.'%',
            ],
            'averages' => [
                'order_value' => $this->averageOrderValue,
                'profit_per_order' => $this->getAverageProfitPerOrder(),
                'cost_per_order' => $this->getAverageCostPerOrder(),
            ],
            'profitability' => [
                'profitable_orders' => $this->getProfitableOrderCount(),
                'profitability_rate' => $this->getProfitabilityRate().'%',
                'roi' => $this->getROI().'%',
            ],
        ];
    }
}
