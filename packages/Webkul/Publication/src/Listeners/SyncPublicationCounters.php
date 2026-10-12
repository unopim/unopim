<?php

namespace Webkul\Publication\Listeners;

use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Events\PublicationReinstated;
use Webkul\Publication\Events\PublicationWithdrawn;
use Webkul\Publication\Models\PublicationProxy;

/**
 * Maintains the two denormalised grid columns. A plain query-builder update
 * (not $publication->update()) deliberately bypasses Eloquent events — these
 * are derived counters, not attested state, and firing history/audit
 * machinery for them on every publish would be noise.
 *
 * Live means publicly reachable, so a withdrawn or redacted passport counts
 * zero however many current versions it holds: the public routes gate on the
 * publication's status, not on the versions. `last_published_at` stays put —
 * it is history, not a live signal.
 */
class SyncPublicationCounters
{
    public function handle(PublicationPublished|PublicationWithdrawn|PublicationReinstated $event): void
    {
        $publication = $event->publication;

        $currentVersions = $publication->versions()->where('is_current', true);

        PublicationProxy::modelClass()::query()->whereKey($publication->id)->update([
            'live_locale_count' => $publication->status === PublicationStatus::Published
                ? $currentVersions->count()
                : 0,
            'last_published_at' => $currentVersions->max('published_at'),
        ]);
    }
}
