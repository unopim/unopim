<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class ExportBatch implements ShouldQueue
{
    use Batchable, \Illuminate\Foundation\Queue\Queueable;

    public $tries = 3;

    /**
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $exportBatch
     */
    public function __construct(
        protected $exportBatch,
        protected $filePath,
        protected $jobTrackId,
        protected $exportBuffer
    ) {
        $count = is_countable($exportBatch->data ?? null) ? count($exportBatch->data) : 0;

        $this->timeout = max(600, $count * 3);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = JobLogger::make($this->jobTrackId);

        $exportHelper = resolve(ExportHelper::class)
            ->setExport($this->exportBatch->jobTrack)
            ->setLogger($logger)
            ->setExportBuffer($this->exportBuffer);

        if ($exportHelper->shouldStop()) {
            $logger->info("ExportBatch #{$this->exportBatch->id} skipped — export was stopped.");

            $this->batch()?->cancel();

            return;
        }

        $logger->info("ExportBatch #{$this->exportBatch->id} started processing.");

        $exportHelper->getTypeExporter()
            ->exportBatch($this->exportBatch, $this->filePath);

        $logger->info("ExportBatch #{$this->exportBatch->id} completed.");
    }

    /**
     * Mark the batch and its export job as failed so the tracker stops showing it as processing.
     */
    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->jobTrackId)->error("ExportBatch #{$this->exportBatch->id} failed: {$exception->getMessage()}", [
            'batch_id'  => $this->exportBatch->id,
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->exportBatch->state = ExportHelper::STATE_FAILED;
        $this->exportBatch->save();

        $jobTrack = $this->exportBatch->jobTrack()->first();

        if ($jobTrack && $jobTrack->state !== ExportHelper::STATE_FAILED) {
            $jobTrack->forceFill([
                'state'        => ExportHelper::STATE_FAILED,
                'errors'       => array_merge((array) $jobTrack->errors, [$exception->getMessage()]),
                'completed_at' => now(),
            ])->save();
        }
    }
}
