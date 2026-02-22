<?php

namespace Webkul\Pricing\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Immutable value object representing recommended prices for a product on a channel.
 *
 * Contains three price tiers (minimum, target, premium) along with the break-even
 * baseline, currency, and the pricing strategy that produced these recommendations.
 */
final class PriceRecommendation implements Arrayable, JsonSerializable
{
    /**
     * @param  int         $channelId       The channel these recommendations apply to.
     * @param  string      $channelName     Human-readable channel name.
     * @param  array{price: float, margin: float} $minimum  Price/margin at minimum margin tier.
     * @param  array{price: float, margin: float} $target   Price/margin at target margin tier.
     * @param  array{price: float, margin: float} $premium  Price/margin at premium margin tier.
     * @param  float       $breakEvenPrice  The break-even price used as the calculation basis.
     * @param  string      $currency        ISO 4217 currency code.
     * @param  string      $strategy        Name or identifier of the PricingStrategy used.
     */
    public function __construct(
        public readonly int $channelId,
        public readonly string $channelName,
        public readonly array $minimum,
        public readonly array $target,
        public readonly array $premium,
        public readonly float $breakEvenPrice,
        public readonly string $currency,
        public readonly string $strategy,
    ) {}

    /**
     * Get the price for a specific tier.
     *
     * @param  string  $tier  One of: minimum, target, premium.
     *
     * @throws \InvalidArgumentException If the tier is not recognized.
     */
    public function priceForTier(string $tier): float
    {
        return match ($tier) {
            'minimum' => $this->minimum['price'],
            'target'  => $this->target['price'],
            'premium' => $this->premium['price'],
            default   => throw new \InvalidArgumentException("Unknown price tier: {$tier}. Valid tiers: minimum, target, premium."),
        };
    }

    /**
     * Get the margin percentage for a specific tier.
     *
     * @param  string  $tier  One of: minimum, target, premium.
     *
     * @throws \InvalidArgumentException If the tier is not recognized.
     */
    public function marginForTier(string $tier): float
    {
        return match ($tier) {
            'minimum' => $this->minimum['margin'],
            'target'  => $this->target['margin'],
            'premium' => $this->premium['margin'],
            default   => throw new \InvalidArgumentException("Unknown price tier: {$tier}. Valid tiers: minimum, target, premium."),
        };
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'channel_id'       => $this->channelId,
            'channel_name'     => $this->channelName,
            'minimum'          => [
                'price'  => round($this->minimum['price'], 4),
                'margin' => round($this->minimum['margin'], 2),
            ],
            'target'           => [
                'price'  => round($this->target['price'], 4),
                'margin' => round($this->target['margin'], 2),
            ],
            'premium'          => [
                'price'  => round($this->premium['price'], 4),
                'margin' => round($this->premium['margin'], 2),
            ],
            'break_even_price' => round($this->breakEvenPrice, 4),
            'currency'         => $this->currency,
            'strategy'         => $this->strategy,
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
