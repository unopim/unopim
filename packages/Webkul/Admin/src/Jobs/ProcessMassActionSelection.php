<?php

namespace Webkul\Admin\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;
use Webkul\DataGrid\DataGrid;
use Webkul\Notification\Events\NotificationEvent;

/**
 * Resolves a "select all matching" selection on the queue and batches one chunk job per slice of ids,
 * so the request that triggered the mass action returns immediately whatever the size of the selection.
 */
class ProcessMassActionSelection implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Records per chunk job, kept small because each record fires its own events and must finish inside the worker timeout.
     */
    public const CHUNK_SIZE = 100;

    /**
     * @param  class-string<DataGrid>  $dataGrid
     * @param  array<string, mixed>  $gridParams
     * @param  class-string<ShouldQueue>  $chunkJob
     * @param  array<int, mixed>  $chunkArguments
     */
    public function __construct(
        protected string $dataGrid,
        protected array $gridParams,
        protected string $chunkJob,
        protected array $chunkArguments = [],
    ) {}

    /**
     * Queue the selection in a batch that notifies the admin once every chunk has run.
     *
     * The resolver runs inside the batch it feeds, so the batch cannot finish before every chunk is added.
     *
     * @param  class-string<DataGrid>  $dataGrid
     * @param  array<string, mixed>  $gridParams
     * @param  class-string<ShouldQueue>  $chunkJob
     * @param  array<int, mixed>  $chunkArguments
     * @param  string  $translationKey  Prefix holding the `title`, `completed` and `failed` notification strings.
     */
    public static function queue(
        string $dataGrid,
        array $gridParams,
        string $chunkJob,
        array $chunkArguments,
        int $adminId,
        string $translationKey,
    ): Batch {
        return Bus::batch([new self($dataGrid, $gridParams, $chunkJob, $chunkArguments)])
            ->name($translationKey)
            ->allowFailures()
            ->finally(fn (Batch $batch) => ProcessMassActionSelection::notify($batch, $adminId, $translationKey))
            ->dispatch();
    }

    /**
     * Stream the matching ids and add one chunk job per slice to the batch.
     *
     * The selection size is cached per batch because the batch callbacks are fixed at dispatch time.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $previousRequest = app('request');

        app()->instance('request', Request::create('/', 'GET', $this->gridParams));

        $count = 0;

        try {
            $dataGrid = app($this->dataGrid);

            $dataGrid->getMatchingIds()
                ->chunk(static::CHUNK_SIZE)
                ->each(function (LazyCollection $ids) use (&$count): void {
                    $count += $ids->count();

                    $this->dispatchChunk($ids->map(fn ($id): int => (int) $id)->values()->all());
                });
        } finally {
            app()->instance('request', $previousRequest);
        }

        if ($batch = $this->batch()) {
            Cache::put(self::countCacheKey($batch->id), $count, now()->addDay());
        }
    }

    /**
     * Notify the admin who triggered the mass action that the batch has finished.
     */
    public static function notify(Batch $batch, int $adminId, string $translationKey): void
    {
        event(new NotificationEvent([
            'type'        => 'mass_action',
            'title'       => trans($translationKey.'.title'),
            'description' => trans($translationKey.($batch->hasFailures() ? '.failed' : '.completed'), [
                'count' => Cache::pull(self::countCacheKey($batch->id), 0),
            ]),
            'user_ids'    => [$adminId],
        ]));
    }

    protected static function countCacheKey(string $batchId): string
    {
        return 'mass-action-selection-count:'.$batchId;
    }

    /**
     * @param  array<int, int>  $ids
     */
    protected function dispatchChunk(array $ids): void
    {
        $job = new ($this->chunkJob)($ids, ...$this->chunkArguments);

        if ($batch = $this->batch()) {
            $batch->add([$job]);

            return;
        }

        dispatch($job);
    }
}
