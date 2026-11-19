<?php

namespace Webkul\Publication\Listeners;

use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Models\PublicationVersionDocumentProxy;

/**
 * GDPR Art. 17 erasure must revoke document access immediately, not wait for
 * some future republish: Redacted is sticky, so no later version would ever
 * replace these rows.
 */
class PrunePublicationVersionDocumentsOnRedaction
{
    public function handle(PublicationRedacted $event): void
    {
        PublicationVersionDocumentProxy::modelClass()::query()
            ->where('publication_id', $event->publication->id)
            ->delete();
    }
}
