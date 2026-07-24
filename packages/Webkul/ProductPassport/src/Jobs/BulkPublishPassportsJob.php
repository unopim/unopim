<?php

namespace Webkul\ProductPassport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\PublicationProxy;

/**
 * Orchestrates a bulk publish: loads the selected publications in bounded
 * chunks and re-dispatches one PublishPassportForProductChannelJob per
 * (product, channel) for all that channel's locales. Kept as its own queued
 * job so an arbitrarily large grid selection never runs in the web request.
 */
class BulkPublishPassportsJob implements ShouldQueue
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
        private readonly ?int $publishedById = null,
    ) {
        $this->onQueue(config('publication.queue'));
    }

    public function handle(): void
    {
        PublicationProxy::modelClass()::query()
            ->whereIn('id', $this->publicationIds)
            ->where('type', 'dpp')
            ->with('channel.locales')
            ->chunkById(self::CHUNK, function ($publications): void {
                foreach ($publications as $publication) {
                    $localeIds = $publication->channel?->locales
                        ->pluck('id')
                        ->map(fn ($id): int => (int) $id)
                        ->all() ?? [];

                    if ($localeIds === []) {
                        continue;
                    }

                    // One dispatch per (product, channel) preserves the publish
                    // job's uniqueId() de-dupe and its per-locale, lockForUpdate()
                    // guarded transaction.
                    PublishPassportForProductChannelJob::dispatch(
                        $publication->product_id,
                        $publication->channel_id,
                        'dpp',
                        $localeIds,
                        $this->publishedById,
                    );
                }
            });
    }
}
