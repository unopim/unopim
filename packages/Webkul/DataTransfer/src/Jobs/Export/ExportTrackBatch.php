<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class ExportTrackBatch implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // Adjust as needed

    /**
     * Create a new job instance.
     */
    public function __construct(protected mixed $exportBatch) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportHelper = app(ExportHelper::class);

        $logger = JobLogger::make($this->exportBatch->id);

        $exportHelper->setExport($this->exportBatch);

        $exportHelper->setLogger($logger);

        $logger->info(trans('data_transfer::app.job.started'));

        // Update the state to VALIDATED
        $exportHelper->stateUpdate(ExportHelper::STATE_VALIDATED);

        $exportHelper->started();

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
