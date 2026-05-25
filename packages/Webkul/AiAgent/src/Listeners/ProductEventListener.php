<?php

namespace Webkul\AiAgent\Listeners;

use Webkul\AiAgent\Jobs\AutoEnrichProductJob;
use Webkul\Product\Models\Product;

/**
 * Listens for product lifecycle events and triggers autonomous
 * AI enrichment when the feature is enabled in configuration.
 */
class ProductEventListener
{
    /**
     * Handle product creation — dispatch auto-enrichment if enabled.
     */
    public function afterCreate(Product $product): void
    {
        if (! $this->isAutoEnrichmentEnabled()) {
            return;
        }

        AutoEnrichProductJob::dispatch(
            productId: $product->id,
            locale: app()->getLocale() ?: 'en_US',
            channel: 'default',
        )->delay(now()->addSeconds(5)); // Small delay to ensure product is fully saved
    }

    /**
     * Check if auto-enrichment is enabled in configuration.
     */
    protected function isAutoEnrichmentEnabled(): bool
    {
        return (bool) core()->getConfigData('general.magic_ai.agentic_pim.auto_enrichment');
    }
}
