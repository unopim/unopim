<?php

namespace Webkul\ProductPassport\Services;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PublicationGate;

/**
 * Fails closed: a product publishes a passport only once its template resolves
 * every field the template marks required for that channel and locale.
 */
class TemplateReadinessGate implements PublicationGate
{
    public function __construct(
        private readonly PassportFeature $feature,
        private readonly PassportReadinessService $readiness,
    ) {}

    public function passes(Product $product, Channel $channel, Locale $locale): bool
    {
        return $this->feature->enabledFor($channel)
            && $this->readiness->isReady($product, $channel, $locale);
    }
}
