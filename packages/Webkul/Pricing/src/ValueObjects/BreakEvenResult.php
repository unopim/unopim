<?php

namespace Webkul\Pricing\ValueObjects;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Immutable value object representing the result of a break-even calculation.
 *
 * Encapsulates all inputs and the computed break-even price for a product
 * on an optional channel. Used as the return type of BreakEvenCalculator::calculate().
 */
final class BreakEvenResult implements Arrayable, JsonSerializable
{
    /**
     * @param  int         $productId      The product this calculation applies to.
     * @param  int|null    $channelId      The channel context (null = product-level only).
     * @param  float       $fixedCosts     Sum of per-unit fixed costs (COGS + operational + shipping + overhead).
     * @param  float       $variableRate   Combined variable rate as a decimal (e.g. 0.18 = 18%).
     * @param  float       $breakEvenPrice Computed minimum selling price to cover all costs.
     * @param  string      $currency       ISO 4217 currency code.
     * @param  CarbonImmutable $calculatedAt Timestamp of the calculation.
     */
    public function __construct(
        public readonly int $productId,
        public readonly ?int $channelId,
        public readonly float $fixedCosts,
        public readonly float $variableRate,
        public readonly float $breakEvenPrice,
        public readonly string $currency,
        public readonly CarbonImmutable $calculatedAt,
    ) {}

    /**
     * Get the actual margin percentage if selling at a given price.
     *
     * Margin% = ((price - breakEven) / price) * 100
     */
    public function marginAtPrice(float $price): float
    {
        if ($price <= 0) {
            return 0.0;
        }

        return round((($price - $this->breakEvenPrice) / $price) * 100, 2);
    }

    /**
     * Check whether a proposed price is above the break-even threshold.
     */
    public function isProfitableAt(float $price): bool
    {
        return $price > $this->breakEvenPrice;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id'       => $this->productId,
            'channel_id'       => $this->channelId,
            'fixed_costs'      => round($this->fixedCosts, 4),
            'variable_rate'    => round($this->variableRate, 4),
            'break_even_price' => round($this->breakEvenPrice, 4),
            'currency'         => $this->currency,
            'calculated_at'    => $this->calculatedAt->toIso8601String(),
        ];
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
