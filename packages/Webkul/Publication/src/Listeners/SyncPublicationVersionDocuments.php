<?php

namespace Webkul\Publication\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Models\PublicationVersionDocumentProxy;
use Webkul\Publication\Models\PublicationVersionProxy;

/**
 * Keeps `publication_version_documents` in sync with whichever version is
 * currently is_current for each (publication, locale): every path the new
 * version's payload references becomes servable, and every path only a
 * now-superseded version referenced stops being servable. This is a rebuild
 * of a derived index, not a change to attested content — safe to prune.
 */
class SyncPublicationVersionDocuments
{
    public function handle(PublicationPublished $event): void
    {
        DB::transaction(function () use ($event): void {
            $staleVersionIds = PublicationVersionProxy::modelClass()::query()
                ->where('publication_id', $event->publication->id)
                ->where('locale_id', $event->version->locale_id)
                ->where('id', '!=', $event->version->id)
                ->pluck('id');

            if ($staleVersionIds->isNotEmpty()) {
                PublicationVersionDocumentProxy::modelClass()::query()
                    ->whereIn('publication_version_id', $staleVersionIds)
                    ->delete();
            }

            $paths = collect(data_get($event->version->payload, 'documents', []))
                ->pluck('path')
                ->filter()
                ->unique();

            foreach ($paths as $path) {
                PublicationVersionDocumentProxy::modelClass()::query()->updateOrCreate(
                    ['publication_version_id' => $event->version->id, 'path' => $path],
                    ['publication_id' => $event->publication->id],
                );
            }
        });
    }
}
