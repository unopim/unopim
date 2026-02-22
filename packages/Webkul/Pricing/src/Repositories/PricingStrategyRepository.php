<?php

namespace Webkul\Pricing\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pricing\Contracts\PricingStrategy;

class PricingStrategyRepository extends Repository
{
    public function model(): string
    {
        return PricingStrategy::class;
    }

    /**
     * Resolve the most specific active pricing strategy for a product/channel combination.
     *
     * Resolution order (most specific wins):
     * 1. product-scoped strategy matching the product
     * 2. channel-scoped strategy matching the channel
     * 3. category-scoped strategy (future: requires category lookup)
     * 4. global strategy
     */
    public function resolveForProduct(int $productId, ?int $channelId = null): ?PricingStrategy
    {
        // Product-level strategy (most specific)
        $strategy = $this->model
            ->where('is_active', true)
            ->where('scope_type', 'product')
            ->where('scope_id', $productId)
            ->orderByDesc('priority')
            ->first();

        if ($strategy) {
            return $strategy;
        }

        // Channel-level strategy
        if ($channelId) {
            $strategy = $this->model
                ->where('is_active', true)
                ->where('scope_type', 'channel')
                ->where('scope_id', $channelId)
                ->orderByDesc('priority')
                ->first();

            if ($strategy) {
                return $strategy;
            }
        }

        // Global fallback
        return $this->model
            ->where('is_active', true)
            ->where('scope_type', 'global')
            ->orderByDesc('priority')
            ->first();
    }
}
