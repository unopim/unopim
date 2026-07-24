<?php

namespace Webkul\Publication\Listeners;

use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Models\PublicationProxy;

/**
 * Maintains the two denormalised grid columns. A plain query-builder update
 * (not $publication->update()) deliberately bypasses Eloquent events — these
 * are derived counters, not attested state, and firing history/audit
 * machinery for them on every publish would be noise.
 */
class SyncPublicationCounters
{
    public function handle(PublicationPublished $event): void
    {
        $publication = $event->publication;

        $liveLocaleCount = $publication->versions()->where('is_current', true)->count();
        $lastPublishedAt = $publication->versions()->where('is_current', true)->max('published_at');

        PublicationProxy::modelClass()::query()->whereKey($publication->id)->update([
            'live_locale_count' => $liveLocaleCount,
            'last_published_at' => $lastPublishedAt,
        ]);
    }
}
