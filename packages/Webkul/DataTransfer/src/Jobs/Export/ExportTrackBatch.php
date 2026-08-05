<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class ExportTrackBatch implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public $tries = 3;

    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * Supervises every batch of the export, so its timeout has to scale with
     * the catalog the way ExportBatch's already does. A fixed 300s expires
     * mid-run on a large export, and the three retries then re-enter a job
     * that cannot finish either — leaving the batches orphaned as pending.
     *
     * @param  mixed  $exportBatch
     */
    public function __construct(protected $exportBatch)
    {
        $count = (int) ($exportBatch->summary['total'] ?? $exportBatch->totalCount ?? 0);

        $this->timeout = max(3600, $count);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportHelper = resolve(ExportHelper::class);

        $logger = JobLogger::make($this->exportBatch->id);

        $exportHelper->setExport($this->exportBatch);

        $exportHelper->setLogger($logger);

        $logger->info(trans('data_transfer::app.job.started'));

        // Update the state to VALIDATED
        $exportHelper->stateUpdate(ExportHelper::STATE_VALIDATED);

        try {
            $exportHelper->started();
        } catch (\Exception $e) {
            $this->exportBatch->state = ExportHelper::STATE_FAILED;
            $this->exportBatch->errors = [$e->getMessage()];
            $this->exportBatch->save();

            $logger->error("Export failed: {$e->getMessage()}", [
                'exception' => $e->getTraceAsString(),
            ]);

            return;
        }

        // Check for pending batches

        $pendingBatch = $this->exportBatch->batches->where('state', ExportHelper::STATE_PENDING)->first();

        if ($pendingBatch) {
            // Start the import process
            try {
                $exportHelper->start(null, $this->queue);
            } catch (\Exception $e) {
                $this->exportBatch->state = ExportHelper::STATE_FAILED;
                $this->exportBatch->errors = [$e->getMessage()];
                $this->exportBatch->save();

                $logger->error("Export process failed: {$e->getMessage()}", [
                    'exception' => $e->getTraceAsString(),
                ]);

                return;
            }
        } else {
            $exportHelper->completed();
        }

        // Determine final state based on current state
        $state = match ($this->exportBatch->state) {
            default => ExportHelper::STATE_COMPLETED,
        };
        // Gather stats
        $exportHelper->stats($state);
    }

    public function failed(\Throwable $exception): void
    {
        $logger = JobLogger::make($this->exportBatch->id);

        $logger->error("ExportTrackBatch failed: {$exception->getMessage()}", [
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->exportBatch->state = ExportHelper::STATE_FAILED;
        $this->exportBatch->errors = [$exception->getMessage()];
        $this->exportBatch->save();
    }
}
