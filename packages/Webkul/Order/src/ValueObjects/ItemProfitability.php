<?php

namespace Webkul\Order\ValueObjects;

/**
 * ItemProfitability
 *
 * Value object representing profitability analysis for a single order item.
 * Contains unit-level revenue, cost, profit, and margin calculations.
 *
 * @package Webkul\Order\ValueObjects
 */
readonly class ItemProfitability
{
    /**
     * Create a new ItemProfitability instance.
     *
     * @param  int  $productId  Product ID
     * @param  string  $productName  Product name
     * @param  string  $productSku  Product SKU
     * @param  int  $quantity  Quantity sold
     * @param  float  $unitPrice  Unit price
     * @param  float  $revenue  Total revenue (unitPrice * quantity)
     * @param  float  $unitCost  Unit cost
     * @param  float  $costBasis  Total cost (unitCost * quantity)
     * @param  float  $profit  Net profit (revenue - costBasis)
     * @param  float  $marginPercentage  Profit margin percentage
     */
    public function __construct(
        public int $productId,
        public string $productName,
        public string $productSku,
        public int $quantity,
        public float $unitPrice,
        public float $revenue,
        public float $unitCost,
        public float $costBasis,
        public float $profit,
        public float $marginPercentage
    ) {}

    /**
     * Check if this item is profitable.
     *
     * @return bool
     */
    public function isProfitable(): bool
    {
        return $this->profit > 0;
    }

    /**
     * Get profit per unit.
     *
     * @return float
     */
    public function getProfitPerUnit(): float
    {
        return round($this->unitPrice - $this->unitCost, 2);
    }

    /**
     * Get markup percentage (profit / cost * 100).
     *
     * @return float
     */
    public function getMarkupPercentage(): float
    {
        return $this->unitCost > 0 ? round(($this->getProfitPerUnit() / $this->unitCost) * 100, 2) : 0;
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'revenue' => $this->revenue,
            'unit_cost' => $this->unitCost,
            'cost_basis' => $this->costBasis,
            'profit' => $this->profit,
            'margin_percentage' => $this->marginPercentage,
            'profit_per_unit' => $this->getProfitPerUnit(),
            'markup_percentage' => $this->getMarkupPercentage(),
            'is_profitable' => $this->isProfitable(),
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
