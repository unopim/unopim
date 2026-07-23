<?php

namespace Webkul\Publication\DataTransferObjects;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

/**
 * Stamps a payload with its own identity so a Digital Product Passport
 * remains self-contained and reconstructible without further catalog access
 * across its ten-year retention obligation. Carries the full Channel/Locale
 * models (not just codes) so a builder can resolve anything it needs from
 * them without a second query. New properties may be appended here later
 * (Task 10 and beyond) without another breaking change to PayloadBuilder,
 * since every consumer must access them by name.
 */
readonly class PublicationContext
{
    public function __construct(
        public string $uuid,
        public Channel $channel,
        public Locale $locale,
        public string $url,
    ) {}
}
