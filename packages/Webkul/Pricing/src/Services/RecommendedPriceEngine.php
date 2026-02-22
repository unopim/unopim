<?php

namespace Webkul\Pricing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Pricing\Events\RecommendationApplied;
use Webkul\Pricing\Models\PricingStrategy;
use Webkul\Pricing\Repositories\PricingStrategyRepository;
use Webkul\Pricing\ValueObjects\BreakEvenResult;
use Webkul\Pricing\ValueObjects\PriceRecommendation;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Generates recommended selling prices at three margin tiers (minimum, target, premium)
 * for products across one or more channels.
 *
 * The engine uses the break-even calculator for cost baselines, resolves the applicable
 * pricing strategy per channel, and optionally applies psychological pricing rounding
 * to make prices more consumer-friendly.
 */
class RecommendedPriceEngine
{
    public function __construct(
        protected BreakEvenCalculator $breakEvenCalculator,
        protected PricingStrategyRepository $pricingStrategyRepository,
        protected ChannelRepository $channelRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Generate recommended prices for a product across channels.
     *
     * Returns an array of PriceRecommendation value objects keyed by channel ID.
     * Each recommendation contains minimum, target, and premium price/margin pairs.
     *
     * @param  int             $productId   Product to generate recommendations for.
     * @param  array<int>|null $channelIds  Specific channels, or null for all active channels.
     * @return array<int, PriceRecommendation> Recommendations keyed by channel_id.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If product not found.
     */
    public function recommend(int $productId, ?array $channelIds = null): array
    {
        // Resolve channels
        $channels = $this->resolveChannels($channelIds);

        if ($channels->isEmpty()) {
            Log::notice('No channels available for price recommendations', [
                'product_id'  => $productId,
                'channel_ids' => $channelIds,
            ]);

            return [];
        }

        // Batch calculate break-even for all channels
        $channelIdList = $channels->pluck('id')->all();
        $recommendations = [];

        foreach ($channels as $channel) {
            try {
                $breakEven = $this->breakEvenCalculator->calculate(
                    $productId,
                    $channel->id
                );

                $strategy = $this->resolveStrategy($productId, $channel->id);

                $recommendation = $this->buildRecommendation(
                    $channel,
                    $breakEven,
                    $strategy
                );

                $recommendations[$channel->id] = $recommendation;
            } catch (\Webkul\Pricing\Exceptions\ImpossibleBreakEvenException $e) {
                Log::warning('Skipping channel due to impossible break-even', [
                    'product_id' => $productId,
                    'channel_id' => $channel->id,
                    'error'      => $e->getMessage(),
                ]);

                continue;
            } catch (\Throwable $e) {
                Log::error('Failed to generate recommendation for channel', [
                    'product_id' => $productId,
                    'channel_id' => $channel->id,
                    'error'      => $e->getMessage(),
                ]);

                continue;
            }
        }

        Log::debug('Price recommendations generated', [
            'product_id'    => $productId,
            'channel_count' => count($recommendations),
        ]);

        return $recommendations;
    }

    /**
     * Apply a recommended price to a product on a specific channel.
     *
     * Updates the product's price attribute value for the given channel
     * and fires the RecommendationApplied event.
     *
     * @param  int          $productId      Product to update.
     * @param  int          $channelId      Channel to apply the price for.
     * @param  string       $tier           Price tier to apply: 'minimum', 'target', or 'premium'.
     * @param  float|null   $overridePrice  Optional manual price override (ignores tier calculation).
     * @return bool         True if the price was successfully applied.
     *
     * @throws \InvalidArgumentException If the tier is not recognized.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If product not found.
     */
    public function apply(int $productId, int $channelId, string $tier, ?float $overridePrice = null): bool
    {
        $validTiers = ['minimum', 'target', 'premium'];

        if (! in_array($tier, $validTiers)) {
            throw new \InvalidArgumentException(
                "Invalid price tier '{$tier}'. Valid tiers: ".implode(', ', $validTiers).'.'
            );
        }

        // Determine the price to apply
        if ($overridePrice !== null) {
            $price = $overridePrice;
        } else {
            $recommendations = $this->recommend($productId, [$channelId]);

            if (! isset($recommendations[$channelId])) {
                Log::error('No recommendation available for channel', [
                    'product_id' => $productId,
                    'channel_id' => $channelId,
                    'tier'       => $tier,
                ]);

                return false;
            }

            $price = $recommendations[$channelId]->priceForTier($tier);
        }

        return DB::transaction(function () use ($productId, $channelId, $tier, $price) {
            // Load the product and update its price in the JSON values column
            $product = $this->productRepository->findOrFail($productId);

            $values = $product->values ?? [];
            $channelSpecific = $values['channel_specific'] ?? [];
            $channelKey = "channel_{$channelId}";

            $channelSpecific[$channelKey] = array_merge(
                $channelSpecific[$channelKey] ?? [],
                ['price' => $price]
            );

            $values['channel_specific'] = $channelSpecific;

            $product->update(['values' => $values]);

            event(new RecommendationApplied(
                productId: $productId,
                channelId: $channelId,
                tier: $tier,
                price: $price,
            ));

            Log::info('Price recommendation applied', [
                'product_id' => $productId,
                'channel_id' => $channelId,
                'tier'       => $tier,
                'price'      => $price,
            ]);

            return true;
        });
    }

    /**
     * Apply psychological pricing rounding to a raw price.
     *
     * Psychological pricing adjusts the price to end in a specific digit pattern
     * (e.g. $12.99 instead of $13.02) to increase perceived value.
     *
     * @param  float   $price    The raw calculated price.
     * @param  string  $roundTo  Rounding target: '0.99', '0.95', '0.00', or 'none'.
     * @return float   The psychologically-rounded price.
     */
    public function applyPsychologicalPricing(float $price, string $roundTo = '0.99'): float
    {
        if ($roundTo === 'none' || $price <= 0) {
            return round($price, 2);
        }

        $wholePart = floor($price);
        $fractionalPart = (float) bcsub((string) $price, (string) $wholePart, 4);

        return match ($roundTo) {
            '0.99' => $this->roundToNinetyNine($price, $wholePart, $fractionalPart),
            '0.95' => $this->roundToNinetyFive($price, $wholePart, $fractionalPart),
            '0.00' => $this->roundToWholeNumber($price),
            default => round($price, 2),
        };
    }

    /**
     * Build a PriceRecommendation value object for a channel.
     */
    protected function buildRecommendation(
        $channel,
        BreakEvenResult $breakEven,
        PricingStrategy $strategy,
    ): PriceRecommendation {
        $usePsychPricing = (bool) $strategy->psychological_pricing;
        $roundTo = $strategy->round_to ?? 'none';

        // Calculate raw prices for each tier
        $minimumRaw = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->minimum_margin_percentage);
        $targetRaw = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->target_margin_percentage);
        $premiumRaw = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->premium_margin_percentage);

        // Apply psychological pricing if enabled
        $minimumPrice = $usePsychPricing ? $this->applyPsychologicalPricing($minimumRaw, $roundTo) : round($minimumRaw, 2);
        $targetPrice = $usePsychPricing ? $this->applyPsychologicalPricing($targetRaw, $roundTo) : round($targetRaw, 2);
        $premiumPrice = $usePsychPricing ? $this->applyPsychologicalPricing($premiumRaw, $roundTo) : round($premiumRaw, 2);

        // Recalculate actual margins after rounding (may differ slightly from target)
        $minimumActualMargin = $this->computeMarginPercentage($minimumPrice, $breakEven->breakEvenPrice);
        $targetActualMargin = $this->computeMarginPercentage($targetPrice, $breakEven->breakEvenPrice);
        $premiumActualMargin = $this->computeMarginPercentage($premiumPrice, $breakEven->breakEvenPrice);

        // Determine strategy label
        $strategyLabel = $strategy->exists
            ? sprintf('%s #%d (scope: %s)', class_basename($strategy), $strategy->id, $strategy->scope_type)
            : 'default (in-memory)';

        return new PriceRecommendation(
            channelId: $channel->id,
            channelName: $channel->name ?? $channel->code ?? "Channel #{$channel->id}",
            minimum: [
                'price'  => $minimumPrice,
                'margin' => $minimumActualMargin,
            ],
            target: [
                'price'  => $targetPrice,
                'margin' => $targetActualMargin,
            ],
            premium: [
                'price'  => $premiumPrice,
                'margin' => $premiumActualMargin,
            ],
            breakEvenPrice: $breakEven->breakEvenPrice,
            currency: $breakEven->currency,
            strategy: $strategyLabel,
        );
    }

    /**
     * Resolve channels from explicit IDs or fall back to all active channels.
     *
     * @param  array<int>|null  $channelIds
     * @return \Illuminate\Support\Collection
     */
    protected function resolveChannels(?array $channelIds)
    {
        if ($channelIds !== null && ! empty($channelIds)) {
            return $this->channelRepository->getModel()
                ->newQuery()
                ->whereIn('id', $channelIds)
                ->get();
        }

        return core()->getAllChannels();
    }

    /**
     * Resolve the applicable PricingStrategy for a product/channel.
     *
     * Falls back to a default in-memory strategy with standard margins.
     */
    protected function resolveStrategy(int $productId, ?int $channelId): PricingStrategy
    {
        $strategy = $this->pricingStrategyRepository->resolveForProduct($productId, $channelId);

        if ($strategy) {
            return $strategy;
        }

        return new PricingStrategy([
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
     * Calculate the price needed to achieve a given margin percentage above break-even.
     *
     * Formula: price = breakEven / (1 - margin% / 100)
     */
    protected function priceForMargin(float $breakEvenPrice, float $marginPercentage): float
    {
        if ($marginPercentage >= 100) {
            return PHP_FLOAT_MAX;
        }

        if ($breakEvenPrice <= 0) {
            return 0.0;
        }

        $marginDecimal = bcdiv((string) $marginPercentage, '100', 4);
        $denominator = bcsub('1', $marginDecimal, 4);
        $result = bcdiv((string) $breakEvenPrice, $denominator, 4);

        return (float) $result;
    }

    /**
     * Compute margin percentage: ((price - breakEven) / price) * 100
     */
    protected function computeMarginPercentage(float $price, float $breakEvenPrice): float
    {
        if ($price <= 0) {
            return -100.0;
        }

        $diff = bcsub((string) $price, (string) $breakEvenPrice, 4);
        $ratio = bcdiv($diff, (string) $price, 4);
        $result = bcmul($ratio, '100', 2);

        return (float) $result;
    }

    /**
     * Round to nearest .99 ending (e.g. 12.34 -> 12.99, 13.02 -> 12.99).
     *
     * The rounding goes to the nearest whole number, then subtracts 0.01.
     * If the price is already very close to .99, it stays.
     */
    protected function roundToNinetyNine(float $price, float $wholePart, float $fractionalPart): float
    {
        // If fractional part > 0.99, round up to next .99
        if ($fractionalPart > 0.99) {
            return $wholePart + 1.99;
        }

        // If fractional part >= 0.50, use current whole + 0.99
        if ($fractionalPart >= 0.50) {
            return $wholePart + 0.99;
        }

        // If fractional < 0.50 and whole > 0, use (whole - 1) + 0.99 but only if
        // the result is still >= the break-even. Otherwise use whole + 0.99.
        if ($wholePart > 0) {
            // Use the nearest .99 that is >= the raw price to avoid under-pricing
            return $wholePart + 0.99;
        }

        return round($price, 2);
    }

    /**
     * Round to nearest .95 ending.
     */
    protected function roundToNinetyFive(float $price, float $wholePart, float $fractionalPart): float
    {
        if ($fractionalPart > 0.95) {
            return $wholePart + 1.95;
        }

        if ($fractionalPart >= 0.50) {
            return $wholePart + 0.95;
        }

        if ($wholePart > 0) {
            return $wholePart + 0.95;
        }

        return round($price, 2);
    }

    /**
     * Round to the nearest whole number (always rounding up to avoid under-pricing).
     */
    protected function roundToWholeNumber(float $price): float
    {
        return (float) ceil($price);
    }
}
