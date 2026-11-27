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
     * Number of SKUs resolved per product lookup, kept well under the database placeholder limit.
     */
    protected const SKU_LOOKUP_CHUNK_SIZE = 1000;

    /**
     * Number of import batches read into memory at a time while collecting SKUs.
     */
    protected const BATCH_READ_CHUNK_SIZE = 50;

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

        $ids = [];

        foreach (array_chunk($this->processedSkus(), self::SKU_LOOKUP_CHUNK_SIZE) as $skus) {
            array_push($ids, ...DB::table('products')->whereIn('sku', $skus)->pluck('id')->all());
        }

        if ($ids !== []) {
            dispatch(new BulkProductCompletenessJob($ids));
        }
    }

    /**
     * Collect the unique SKUs of every processed batch, reading the batches one chunk at a time.
     *
     * @return array<int, string>
     */
    protected function processedSkus(): array
    {
        $skus = [];

        $batches = $this->import->batches()
            ->where('state', ImportHelper::STATE_PROCESSED)
            ->select(['id', 'data'])
            ->lazyById(self::BATCH_READ_CHUNK_SIZE);

        foreach ($batches as $batch) {
            foreach (array_column($batch->data ?? [], 'sku') as $sku) {
                if (filled($sku)) {
                    $skus[$sku] = true;
                }
            }
        }

        return array_map(strval(...), array_keys($skus));
    }
}
