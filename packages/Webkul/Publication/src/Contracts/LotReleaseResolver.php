<?php

namespace Webkul\Publication\Contracts;

use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationRelease;

/**
 * Maps a scanned GS1 lot (AI 10) and/or serial (AI 21) to the release a unit was placed on the market under.
 *
 * Which release that is lives outside the PIM (batch records, ERP), so the engine only defines the question.
 * Return null when the mapping is unknown; the scan then lands on the live passport, as an unqualified link does.
 */
interface LotReleaseResolver
{
    public function resolve(Publication $publication, ?string $lot, ?string $serial): ?PublicationRelease;
}
