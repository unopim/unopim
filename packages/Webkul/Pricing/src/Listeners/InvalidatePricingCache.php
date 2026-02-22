<?php

namespace Webkul\Pricing\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Pricing\Events\CostUpdated;
use Webkul\Pricing\Events\MarginApproved;
use Webkul\Pricing\Events\RecommendationApplied;
use Webkul\Pricing\Repositories\ChannelCostRepository;

/**
 * Invalidates pricing-related caches when costs, margins, or recommendations change.
 *
 * This listener ensures that cached break-even calculations, recommendations,
 * and cost summaries are cleared whenever their underlying data changes.
 *
 * Cache key format used by BreakEvenCalculator:
 *   pricing:breakeven:{tenantId}:{productId}:{channelId|all}
 */
class InvalidatePricingCache
{
    public function __construct(
        protected ChannelCostRepository $channelCostRepository,
    ) {}

    /**
     * Handle cost updated events — invalidate break-even and cost caches.
     */
    public function handleCostUpdated(CostUpdated $event): void
    {
        $productCost = $event->productCost;
        $productId = $productCost->product_id;
        $tenantId = $productCost->tenant_id ?? (core()->getCurrentTenantId() ?? 0);

        // Invalidate product-level break-even cache (no specific channel)
        $this->forgetBreakEvenKey($tenantId, $productId, 'all');

        // Invalidate all channel-specific break-even caches for this product
        $this->invalidateAllChannelCaches($tenantId, $productId);

        // Invalidate cost summary cache
        Cache::forget("pricing:costs:{$productId}");

        // Invalidate recommendations cache
        Cache::forget("pricing:recommendations:{$productId}");

        Log::debug('Pricing caches invalidated for cost update', [
            'product_id' => $productId,
            'tenant_id'  => $tenantId,
            'cost_type'  => $productCost->cost_type,
        ]);
    }

    /**
     * Handle margin approved events — invalidate break-even caches.
     */
    public function handleMarginApproved(MarginApproved $event): void
    {
        $marginEvent = $event->marginEvent;
        $productId = $marginEvent->product_id;
        $tenantId = $marginEvent->tenant_id ?? (core()->getCurrentTenantId() ?? 0);

        // Invalidate all break-even caches for this product
        $this->forgetBreakEvenKey($tenantId, $productId, 'all');
        $this->invalidateAllChannelCaches($tenantId, $productId);

        // Invalidate recommendations
        Cache::forget("pricing:recommendations:{$productId}");

        Log::debug('Pricing caches invalidated for margin approval', [
            'product_id' => $productId,
            'event_id'   => $marginEvent->id,
        ]);
    }

    /**
     * Handle recommendation applied events — invalidate recommendation caches.
     */
    public function handleRecommendationApplied(RecommendationApplied $event): void
    {
        Cache::forget("pricing:recommendations:{$event->productId}");

        Log::debug('Recommendation cache invalidated', [
            'product_id' => $event->productId,
            'channel_id' => $event->channelId,
        ]);
    }

    /**
     * Invalidate all channel-specific break-even caches for a product.
     *
     * Since BreakEvenCalculator creates per-channel cache keys, we need to
     * query all channels and invalidate each one when costs change.
     */
    protected function invalidateAllChannelCaches(int $tenantId, int $productId): void
    {
        try {
            $channelCosts = $this->channelCostRepository->all();

            foreach ($channelCosts as $channelCost) {
                $this->forgetBreakEvenKey($tenantId, $productId, $channelCost->channel_id);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to invalidate channel-specific caches', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Forget a specific break-even cache key.
     *
     * Uses the same key format as BreakEvenCalculator::buildCacheKey():
     *   pricing:breakeven:{tenantId}:{productId}:{channelId|all}
     */
    protected function forgetBreakEvenKey(int $tenantId, int $productId, int|string $channelPart): void
    {
        $key = "pricing:breakeven:{$tenantId}:{$productId}:{$channelPart}";
        Cache::forget($key);
    }
}
