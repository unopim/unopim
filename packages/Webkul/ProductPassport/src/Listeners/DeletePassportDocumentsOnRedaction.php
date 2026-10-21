<?php

namespace Webkul\ProductPassport\Listeners;

use Illuminate\Support\Facades\Storage;
use Webkul\Publication\Events\PublicationRedacted;

/**
 * Deletes the document copies PassportPayloadBuilder wrote under `publication/{uuid}/` on the asset
 * disk once the publication is redacted, so an erasure leaves no readable copy of them behind.
 *
 * Every version is redacted by then, so no payload references these files any more. The directory is
 * scoped to the publication's uuid, so no other publication shares it, and a later republish copies
 * the documents afresh.
 */
class DeletePassportDocumentsOnRedaction
{
    public function handle(PublicationRedacted $event): void
    {
        Storage::disk(config('publication.asset_disk'))->deleteDirectory('publication/'.$event->publication->uuid);
    }
}
