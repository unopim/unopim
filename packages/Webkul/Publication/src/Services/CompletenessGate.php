<?php

namespace Webkul\Publication\Services;

use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

class CompletenessGate
{
    /**
     * A missing score fails closed: a locale that has never been scored has
     * never been proven complete, so publishing it is refused rather than
     * allowed by default.
     */
    public function passes(Product $product, Channel $channel, Locale $locale): bool
    {
        $configured = core()->getConfigData('catalog.product_passport.settings.completeness_threshold', $channel->code);

        // Only an unconfigured (null/empty) setting falls back to 100. A
        // deliberately configured threshold of 0 must mean 0, not "unset".
        $threshold = $configured === null || $configured === ''
            ? 100
            : (int) $configured;

        $score = ProductCompletenessScore::query()
            ->where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->where('locale_id', $locale->id)
            ->value('score');

        return $score !== null && $score >= $threshold;
    }
}
