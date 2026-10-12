<?php

namespace Webkul\Publication\DataTransferObjects;

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

/**
 * Identity stamped onto a payload so it stays self-contained and
 * reconstructible without further catalog access. Carries the full
 * Channel/Locale models, not just codes, so a builder can resolve anything it
 * needs without a second query.
 *
 * `$preview` requests a side-effect-free build for the admin preview screen: a
 * builder honouring it must skip every persistent write (e.g. copying assets to
 * the publication disk) so rendering a preview never touches storage. It
 * defaults false, so real publishing is unaffected.
 */
readonly class PublicationContext
{
    public function __construct(
        public string $uuid,
        public Channel $channel,
        public Locale $locale,
        public string $url,
        public bool $preview = false,
    ) {}
}
