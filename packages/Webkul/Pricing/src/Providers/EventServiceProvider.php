<?php

namespace Webkul\Pricing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Pricing\Events\CostUpdated;
use Webkul\Pricing\Events\MarginApproved;
use Webkul\Pricing\Events\MarginBlocked;
use Webkul\Pricing\Events\RecommendationApplied;
use Webkul\Pricing\Listeners\InvalidatePricingCache;
use Webkul\Pricing\Listeners\NotifyMarginViolation;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CostUpdated::class => [
            [InvalidatePricingCache::class, 'handleCostUpdated'],
        ],
        MarginBlocked::class => [
            NotifyMarginViolation::class,
        ],
        MarginApproved::class => [
            [InvalidatePricingCache::class, 'handleMarginApproved'],
        ],
        RecommendationApplied::class => [
            [InvalidatePricingCache::class, 'handleRecommendationApplied'],
        ],
    ];

    /**
     * Register any events for your application (F-008: orphan strategy cleanup).
     */
    public function boot(): void
    {
        parent::boot();

        // F-008: Clean up orphan pricing strategies when a product/channel/category is deleted
        $this->app['events']->listen('eloquent.deleted:*', function (string $event, array $models) {
            foreach ($models as $model) {
                $this->cleanupOrphanStrategies($model);
            }
        });
    }

    /**
     * Delete orphaned pricing strategies when their scope entity is deleted.
     */
    protected function cleanupOrphanStrategies($model): void
    {
        $scopeType = match (true) {
            $model instanceof \Webkul\Product\Models\Product   => 'product',
            $model instanceof \Webkul\Core\Models\Channel      => 'channel',
            $model instanceof \Webkul\Category\Models\Category => 'category',
            default => null,
        };

        if ($scopeType === null) {
            return;
        }

        try {
            \Webkul\Pricing\Models\PricingStrategy::query()
                ->where('scope_type', $scopeType)
                ->where('scope_id', $model->id)
                ->delete();

            \Illuminate\Support\Facades\Log::debug('Orphan strategies cleaned up', [
                'scope_type' => $scopeType,
                'scope_id'   => $model->id,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to cleanup orphan strategies', [
                'scope_type' => $scopeType,
                'scope_id'   => $model->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
