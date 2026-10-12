<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Completed implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     */
    public function __construct(protected $import, protected $jobTrackId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = JobLogger::make($this->jobTrackId);

        $importHelper = resolve(ImportHelper::class)
            ->setImport($this->import)
            ->setLogger($logger);

        if ($importHelper->shouldStop()) {
            $logger->info('Completed job skipped — import was stopped.');

            return;
        }

        $logger->info('Finalizing import — aggregating summary.');

        $importHelper->completed();

        $this->dispatchPostImportCompleteness();
    }

    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->jobTrackId)->error("Completed job failed: {$exception->getMessage()}", [
            'exception' => $exception->getTraceAsString(),
        ]);

        if ($this->import && $this->import->state !== ImportHelper::STATE_FAILED) {
            $this->import->state = ImportHelper::STATE_FAILED;
            $this->import->completed_at = now();
            $this->import->save();
        }
    }

    /**
     * Dispatch a single completeness job on the dedicated completeness queue
     * after ALL import batches have finished. This decouples completeness
     * from the import pipeline — a separate worker can consume it independently.
     *
     * Only fires for product imports (entity_type === 'products').
     */
    protected function dispatchPostImportCompleteness(): void
    {
        if ($this->import->jobInstance->entity_type !== 'products') {
            return;
        }

        if ($this->import->action === ImportHelper::ACTION_DELETE) {
            return;
        }

        // Collect all SKUs from all processed batches in a single query
        $skus = $this->import->batches()
            ->where('state', ImportHelper::STATE_PROCESSED)
            ->get('data')
            ->flatMap(fn ($batch): array => array_column($batch->data ?? [], 'sku'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($skus)) {
            return;
        }

        // Resolve product IDs from SKUs in one query
        $ids = DB::table('products')
            ->whereIn('sku', $skus)
            ->pluck('id')
            ->toArray();

        if (! empty($ids)) {
            dispatch(new BulkProductCompletenessJob($ids));
        }
    }
}
