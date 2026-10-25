<?php

namespace Webkul\Publication\Services;

use Webkul\Publication\Contracts\LotReleaseResolver;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationRelease;

/**
 * Default binding: no lot-to-release knowledge, so every qualified scan resolves like an unqualified one.
 */
class NullLotReleaseResolver implements LotReleaseResolver
{
    public function resolve(Publication $publication, ?string $lot, ?string $serial): ?PublicationRelease
    {
        return null;
    }
}
