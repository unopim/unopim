<?php

namespace Webkul\Publication\Listeners;

use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Models\PublicationVersionDocumentProxy;

/**
 * GDPR Art. 17 erasure must revoke document access immediately, not wait for
 * some future republish that may never come — a redacted publication can
 * still mint a fresh version later (see Publisher::redactAll()'s docblock),
 * at which point SyncPublicationVersionDocuments repopulates this table
 * normally for that locale.
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
