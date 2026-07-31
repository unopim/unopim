<?php

namespace Webkul\ProductPassport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Services\Publisher;

/**
 * Withdraws or reinstates a grid selection in chunks.
 *
 * Each row goes through Publisher rather than a blind mass UPDATE, so the
 * withdrawal events and the derived counters stay correct; rows already in the
 * target state (or redacted, which is one-way) are skipped instead of failing
 * the batch.
 */
class BulkTransitionPassportsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const CHUNK = 200;

    /**
     * @param  list<int>  $publicationIds
     */
    public function __construct(
        private readonly array $publicationIds,
        private readonly PublicationStatus $target,
    ) {
        $this->onQueue(config('publication.queue'));
    }

    public function handle(Publisher $publisher): void
    {
        PublicationProxy::modelClass()::query()
            ->whereIn('id', $this->publicationIds)
            ->where('type', 'dpp')
            ->where('status', $this->target === PublicationStatus::Withdrawn
                ? PublicationStatus::Published->value
                : PublicationStatus::Withdrawn->value)
            ->chunkById(self::CHUNK, function ($publications) use ($publisher): void {
                foreach ($publications as $publication) {
                    try {
                        $this->target === PublicationStatus::Withdrawn
                            ? $publisher->withdraw($publication)
                            : $publisher->reinstate($publication);
                    } catch (InvalidPublicationTransitionException) {
                        continue;
                    }
                }
            });
    }
}
