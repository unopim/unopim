<?php

namespace Webkul\ProductPassport\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationPublishRefused;
use Webkul\Publication\Events\PublicationRedacted;

/**
 * Deletes the document copies PassportPayloadBuilder wrote under `publication/{uuid}/` on the asset
 * disk once a passport is redacted, so an erasure leaves no readable copy of them behind.
 *
 * Every version is redacted by then, so no payload references these files any more, and Redacted is
 * sticky, so a publish that starts after the redaction cannot copy them back. A publish already past
 * its status check when the redaction commits may still copy them; its lock-time refusal dispatches
 * PublicationPublishRefused, which runs this cleanup again. It acts only on a passport that is Redacted
 * at that point, and deleting is idempotent. The directory is scoped to the publication's uuid, so no
 * other publication shares it.
 *
 * Queued, so a storage error never fails the request that committed the redaction: a failed delete
 * throws and is retried with backoff, and after the last attempt it lands in the failed jobs table,
 * where it can be retried once the disk is healthy again.
 */
class DeletePassportDocumentsOnRedaction implements ShouldQueue
{
    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function viaQueue(): string
    {
        return (string) config('publication.queue');
    }

    public function handle(PublicationRedacted|PublicationPublishRefused $event): void
    {
        if ($event->publication->type !== 'dpp' || $event->publication->status !== PublicationStatus::Redacted) {
            return;
        }

        $directory = 'publication/'.$event->publication->uuid;

        if (! Storage::disk(config('publication.asset_disk'))->deleteDirectory($directory)) {
            throw new RuntimeException('Could not delete the document copies of redacted publication '.$event->publication->id.' under '.$directory.'.');
        }
    }
}
