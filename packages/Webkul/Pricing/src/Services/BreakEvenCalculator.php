<?php

namespace Webkul\Pricing\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Pricing\Repositories\ChannelCostRepository;
use Webkul\Pricing\Repositories\ProductCostRepository;
use Webkul\Pricing\ValueObjects\BreakEvenResult;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Calculates the break-even price for products based on their cost structure.
 *
 * Break-even is the minimum price at which a product must sell to cover all costs
 * (fixed per-unit costs and variable percentage-based costs from channel fees).
 *
 * Formula: breakEvenPrice = totalFixedCosts / (1 - totalVariableRate)
 *
 * Fixed costs (per unit):  COGS + operational + shipping + overhead
 * Variable costs (rates):  channel commission% + payment processing% + marketing%
 *
 * All monetary calculations use BCMath for precision (scale=4 for prices, scale=6 for rates).
 */
class BreakEvenCalculator
{
    /**
     * BCMath scale for price calculations.
     */
    protected const PRICE_SCALE = 4;

    /**
     * BCMath scale for rate calculations (higher precision for intermediate values).
     */
    protected const RATE_SCALE = 6;

    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Fixed cost types that contribute to per-unit fixed costs.
     *
     * @var array<string>
     */
    protected const FIXED_COST_TYPES = ['cogs', 'operational', 'shipping', 'overhead'];

    public function __construct(
        protected ProductCostRepository $productCostRepository,
        protected ChannelCostRepository $channelCostRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Calculate break-even price for a product on a specific channel.
     *
     * @param  int          $productId  The product to calculate break-even for.
     * @param  int|null     $channelId  Optional channel for channel-specific variable costs.
     * @param  string|null  $currency   ISO 4217 currency code override (defaults to product cost currency).
     * @return BreakEvenResult          Immutable value object with all calculation details.
     *
     * @throws \Webkul\Pricing\Exceptions\ImpossibleBreakEvenException If variable rate >= 100%.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException    If product not found.
     */
    public function calculate(int $productId, ?int $channelId = null, ?string $currency = null): BreakEvenResult
    {
        $cacheKey = $this->buildCacheKey($productId, $channelId);

        $cached = Cache::get($cacheKey);

        if ($cached instanceof BreakEvenResult) {
            return $cached;
        }

        $result = $this->performCalculation($productId, $channelId, $currency);

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        Log::debug('Break-even calculated', [
            'product_id'       => $productId,
            'channel_id'       => $channelId,
            'break_even_price' => $result->breakEvenPrice,
            'fixed_costs'      => $result->fixedCosts,
            'variable_rate'    => $result->variableRate,
            'currency'         => $result->currency,
        ]);

        return $result;
    }

    /**
     * Calculate break-even for multiple products in batch.
     *
     * Uses bulk-loaded costs to minimize database queries (2 queries total
     * regardless of product count).
     *
     * @param  array<int>   $productIds  Array of product IDs.
     * @param  int|null     $channelId   Optional channel context.
     * @param  string|null  $currency    Currency override.
     * @return array<int, BreakEvenResult> Results keyed by product_id.
     */
    public function calculateBatch(array $productIds, ?int $channelId = null, ?string $currency = null): array
    {
        if (empty($productIds)) {
            return [];
        }

        $productIds = array_unique(array_filter($productIds));

        // Separate cached from uncached
        $results = [];
        $uncachedIds = [];

        foreach ($productIds as $productId) {
            $cacheKey = $this->buildCacheKey($productId, $channelId);
            $cached = Cache::get($cacheKey);

            if ($cached instanceof BreakEvenResult) {
                $results[$productId] = $cached;
            } else {
                $uncachedIds[] = $productId;
            }
        }

        if (empty($uncachedIds)) {
            return $results;
        }

        // Batch load all product costs in one query
        $allProductCosts = $this->productCostRepository->getModel()
            ->newQuery()
            ->whereIn('product_id', $uncachedIds)
            ->active()
            ->get()
            ->groupBy('product_id');

        // Load channel costs once if needed
        $channelCost = null;
        if ($channelId) {
            $channelCost = $this->channelCostRepository->getActiveForChannel($channelId);
        }

        foreach ($uncachedIds as $productId) {
            $productCosts = $allProductCosts->get($productId, collect());

            // Sum fixed costs using BCMath
            $fixedCosts = '0';
            foreach ($productCosts->whereIn('cost_type', self::FIXED_COST_TYPES) as $cost) {
                $fixedCosts = bcadd($fixedCosts, (string) $cost->amount, self::PRICE_SCALE);
            }

            // Get marketing rate (stored as amount representing percentage)
            $marketingRate = '0';
            foreach ($productCosts->where('cost_type', 'marketing') as $cost) {
                $marketingRate = bcadd($marketingRate, (string) $cost->amount, self::RATE_SCALE);
            }

            // Calculate variable rate
            $variableRate = $this->computeVariableRate($channelCost, $marketingRate);

            // Determine currency
            $resolvedCurrency = $currency
                ?? $productCosts->first()?->currency_code
                ?? $channelCost?->currency_code
                ?? 'USD';

            // Calculate break-even
            $breakEvenPrice = $this->computeBreakEven($fixedCosts, $variableRate);

            $result = new BreakEvenResult(
                productId: $productId,
                channelId: $channelId,
                fixedCosts: (float) $fixedCosts,
                variableRate: (float) $variableRate,
                breakEvenPrice: (float) $breakEvenPrice,
                currency: $resolvedCurrency,
                calculatedAt: CarbonImmutable::now(),
            );

            $cacheKey = $this->buildCacheKey($productId, $channelId);
            Cache::put($cacheKey, $result, self::CACHE_TTL);

            $results[$productId] = $result;
        }

        Log::debug('Break-even batch calculated', [
            'product_count' => count($uncachedIds),
            'channel_id'    => $channelId,
            'from_cache'    => count($productIds) - count($uncachedIds),
        ]);

        return $results;
    }

    /**
     * Invalidate cached break-even result for a product (and optionally a channel).
     *
     * @param  int       $productId  The product whose cache to clear.
     * @param  int|null  $channelId  If null, invalidates the product-level cache key only.
     */
    public function invalidateCache(int $productId, ?int $channelId = null): void
    {
        $cacheKey = $this->buildCacheKey($productId, $channelId);

        Cache::forget($cacheKey);

        Log::debug('Break-even cache invalidated', [
            'product_id' => $productId,
            'channel_id' => $channelId,
            'cache_key'  => $cacheKey,
        ]);
    }

    /**
     * Perform the actual break-even calculation for a single product.
     */
    protected function performCalculation(int $productId, ?int $channelId, ?string $currency): BreakEvenResult
    {
        // 1. Load all active costs for this product
        $productCosts = $this->productCostRepository->getActiveCostsForProduct($productId);

        // 2. Sum fixed costs (cogs + operational + shipping + overhead) using BCMath
        $fixedCosts = '0';
        foreach ($productCosts->whereIn('cost_type', self::FIXED_COST_TYPES) as $cost) {
            $fixedCosts = bcadd($fixedCosts, (string) $cost->amount, self::PRICE_SCALE);
        }

        // 3. Get marketing percentage (stored as a cost of type 'marketing')
        $marketingRate = '0';
        foreach ($productCosts->where('cost_type', 'marketing') as $cost) {
            $marketingRate = bcadd($marketingRate, (string) $cost->amount, self::RATE_SCALE);
        }

        // 4. Get channel costs if channelId provided
        $channelCost = null;
        if ($channelId) {
            $channelCost = $this->channelCostRepository->getActiveForChannel($channelId);
        }

        // 5. Compute total variable rate
        $variableRate = $this->computeVariableRate($channelCost, $marketingRate);

        // 6. Determine currency
        $resolvedCurrency = $currency
            ?? $productCosts->first()?->currency_code
            ?? $channelCost?->currency_code
            ?? 'USD';

        // 7. Calculate break-even price
        $breakEvenPrice = $this->computeBreakEven($fixedCosts, $variableRate);

        return new BreakEvenResult(
            productId: $productId,
            channelId: $channelId,
            fixedCosts: (float) $fixedCosts,
            variableRate: (float) $variableRate,
            breakEvenPrice: (float) $breakEvenPrice,
            currency: $resolvedCurrency,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Compute the total variable cost rate from channel fees and marketing percentage.
     *
     * Variable rate = (commission% + payment_processing% + marketing%) / 100
     *
     * @param  \Webkul\Pricing\Models\ChannelCost|null  $channelCost  Active channel cost structure.
     * @param  string  $marketingRate  Marketing percentage from product costs (BCMath string).
     * @return string  The combined variable rate as a decimal string (0.0 to < 1.0).
     */
    protected function computeVariableRate($channelCost, string $marketingRate): string
    {
        $commissionPct = '0';
        $paymentProcessingPct = '0';

        if ($channelCost) {
            $commissionPct = (string) $channelCost->commission_percentage;
            $paymentProcessingPct = (string) $channelCost->payment_processing_percentage;
        }

        $totalPercentage = bcadd($commissionPct, $paymentProcessingPct, self::RATE_SCALE);
        $totalPercentage = bcadd($totalPercentage, $marketingRate, self::RATE_SCALE);

        return bcdiv($totalPercentage, '100', self::RATE_SCALE);
    }

    /**
     * Compute break-even price from fixed costs and variable rate.
     *
     * Formula: breakEven = fixedCosts / (1 - variableRate)
     *
     * @param  string  $fixedCosts    Total per-unit fixed costs (BCMath string).
     * @param  string  $variableRate  Combined variable rate as decimal string (< 1.0).
     * @return string  The break-even price (BCMath string).
     *
     * @throws \Webkul\Pricing\Exceptions\ImpossibleBreakEvenException
     */
    protected function computeBreakEven(string $fixedCosts, string $variableRate): string
    {
        if (bccomp($variableRate, '1', self::RATE_SCALE) >= 0) {
            Log::error('Impossible break-even: variable rate >= 100%', [
                'fixed_costs'   => $fixedCosts,
                'variable_rate' => $variableRate,
            ]);

            throw new \Webkul\Pricing\Exceptions\ImpossibleBreakEvenException(
                "Cannot calculate break-even: total variable cost rate ({$variableRate}) equals or exceeds 100%. "
                .'This means every sale would incur a loss regardless of price. '
                .'Review commission, payment processing, and marketing rates.'
            );
        }

        if (bccomp($fixedCosts, '0', self::PRICE_SCALE) <= 0) {
            return '0.0000';
        }

        $denominator = bcsub('1', $variableRate, self::RATE_SCALE);

        return bcdiv($fixedCosts, $denominator, self::PRICE_SCALE);
    }

    /**
     * Build a tenant-aware cache key for break-even results.
     *
     * Key format: pricing:breakeven:{tenantId}:{productId}:{channelId}
     */
    protected function buildCacheKey(int $productId, ?int $channelId): string
    {
        $tenantId = core()->getCurrentTenantId() ?? 0;
        $channelPart = $channelId ?? 'all';

        return "pricing:breakeven:{$tenantId}:{$productId}:{$channelPart}";
    }
}
