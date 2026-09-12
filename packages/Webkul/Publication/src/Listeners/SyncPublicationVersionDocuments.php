<?php

namespace Webkul\Publication\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Models\PublicationVersionDocumentProxy;

/**
 * Indexes every document path the newly published version references so the
 * asset proxy can serve it.
 *
 * Rows of superseded versions are deliberately kept: a sealed version's
 * documents are attested content and must stay resolvable for as long as the
 * version itself does. Whether a path is *servable* right now is decided at
 * request time by the asset controller against the referencing version's
 * state, not by deleting history here.
 */
class SyncPublicationVersionDocuments
{
    public function handle(PublicationPublished $event): void
    {
        DB::transaction(function () use ($event): void {
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
