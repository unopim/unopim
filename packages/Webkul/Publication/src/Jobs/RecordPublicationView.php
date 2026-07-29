<?php

namespace Webkul\Publication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Webkul\Publication\Models\PublicationViewStatProxy;

/**
 * Records a single passport view as a GDPR-safe daily aggregate: it bumps the
 * (publication, locale, day) counter by one, never storing a per-view row and
 * never a raw IP or any visitor identity.
 *
 * Queued so the public render never waits on the write. A single atomic upsert
 * increments an existing row or seeds it at 1, so concurrent workers on the same
 * day can't lose a count to a read-modify-write race.
 */
class RecordPublicationView implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $publicationId,
        private readonly ?int $localeId = null,
    ) {
        $this->onQueue(config('publication.queue'));
    }

    public function handle(): void
    {
        $model = PublicationViewStatProxy::modelClass();

        DB::table((new $model)->getTable())->upsert(
            [[
                'publication_id' => $this->publicationId,
                'locale_id'      => $this->localeId,
                'viewed_on'      => now()->toDateString(),
                'views'          => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]],
            ['publication_id', 'locale_id', 'viewed_on'],
            ['views' => DB::raw('views + 1'), 'updated_at' => now()],
        );
    }
}
