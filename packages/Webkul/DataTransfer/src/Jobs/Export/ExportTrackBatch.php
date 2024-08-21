<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;

class ExportTrackBatch implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $exportBatch;

    public $tries = 3;

    public $timeout = 300; // Adjust as needed

    /**
     * Create a new job instance.
     *
     * @param  mixed  $exportBatch
     * @return void
     */
    public function __construct($exportBatch)
    {
        $this->exportBatch = $exportBatch;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $exportHelper = app(ExportHelper::class);
        $exportHelper->setExport($this->exportBatch);

        // Update the state to VALIDATED
        $exportHelper->stateUpdate(ExportHelper::STATE_VALIDATED);

        $exportHelper->started();

        // Check for pending batches

        $pendingBatch = $this->exportBatch->batches->where('state', ExportHelper::STATE_PENDING)->first();

        if ($pendingBatch) {
            // Start the import process
            try {
                $exportHelper->start();
            } catch (\Exception $e) {
                $this->exportBatch->state = ExportHelper::STATE_FAILED;
                $this->exportBatch->errors = [$e->getMessage()];
                $this->exportBatch->save();

                \Log::error('Export process failed: '.$e->getMessage());

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
        $stats = $exportHelper->stats($state);
    }
}
