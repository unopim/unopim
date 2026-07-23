<?php

namespace Webkul\Publication\DataTransferObjects;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

/**
 * Identity stamped onto a payload so it stays self-contained and
 * reconstructible without further catalog access. Carries the full
 * Channel/Locale models, not just codes, so a builder can resolve anything it
 * needs without a second query.
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
