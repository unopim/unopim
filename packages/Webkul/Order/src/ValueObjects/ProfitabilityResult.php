<?php

namespace Webkul\Order\ValueObjects;

use Carbon\Carbon;

/**
 * ProfitabilityResult
 *
 * Value object representing profitability analysis for a single order.
 * Contains revenue, cost, profit, margin percentage, and item-level breakdown.
 *
 * @package Webkul\Order\ValueObjects
 */
readonly class ProfitabilityResult
{
    /**
     * Create a new ProfitabilityResult instance.
     *
     * @param  int  $orderId  Order ID
     * @param  string  $orderNumber  Order number
     * @param  float  $revenue  Total revenue
     * @param  float  $totalCost  Total cost
     * @param  float  $profit  Net profit
     * @param  float  $marginPercentage  Profit margin percentage
     * @param  array  $itemBreakdown  Array of ItemProfitability objects
     * @param  string  $currencyCode  Currency code
     * @param  Carbon  $orderDate  Order date
     * @param  int  $channelId  Channel ID
     */
    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public float $revenue,
        public float $totalCost,
        public float $profit,
        public float $marginPercentage,
        public array $itemBreakdown,
        public string $currencyCode,
        public Carbon $orderDate,
        public int $channelId
    ) {}

    /**
     * Check if the order is profitable.
     *
     * @return bool
     */
    public function isProfitable(): bool
    {
        return $this->profit > 0;
    }

    /**
     * Get number of items in the order.
     *
     * @return int
     */
    public function getItemCount(): int
    {
        return count($this->itemBreakdown);
    }

    /**
     * Get average profit per item.
     *
     * @return float
     */
    public function getAverageProfitPerItem(): float
    {
        $itemCount = $this->getItemCount();

        return $itemCount > 0 ? round($this->profit / $itemCount, 2) : 0;
    }

    /**
     * Get cost to revenue ratio.
     *
     * @return float
     */
    public function getCostToRevenueRatio(): float
    {
        return $this->revenue > 0 ? round($this->totalCost / $this->revenue, 4) : 0;
    }

    /**
     * Get formatted currency value.
     *
     * @param  float  $amount
     * @return string
     */
    public function formatCurrency(float $amount): string
    {
        return $this->currencyCode.' '.number_format($amount, 2);
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'revenue' => $this->revenue,
            'total_cost' => $this->totalCost,
            'profit' => $this->profit,
            'margin_percentage' => $this->marginPercentage,
            'is_profitable' => $this->isProfitable(),
            'cost_to_revenue_ratio' => $this->getCostToRevenueRatio(),
            'currency_code' => $this->currencyCode,
            'order_date' => $this->orderDate->toIso8601String(),
            'channel_id' => $this->channelId,
            'item_count' => $this->getItemCount(),
            'average_profit_per_item' => $this->getAverageProfitPerItem(),
            'item_breakdown' => array_map(fn ($item) => $item->toArray(), $this->itemBreakdown),
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
}
