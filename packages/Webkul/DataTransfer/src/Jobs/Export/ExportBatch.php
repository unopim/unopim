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

class ExportBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected mixed $exportBatch,
        protected mixed $filePath,
        protected mixed $jobTrackId,
        protected mixed $exportBuffer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = JobLogger::make($this->jobTrackId);

        $exportHelper = app(ExportHelper::class)
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

    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->jobTrackId)->error("ExportBatch #{$this->exportBatch->id} failed: {$exception->getMessage()}", [
            'batch_id'  => $this->exportBatch->id,
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->exportBatch->state = 'failed';
        $this->exportBatch->save();
    }
}
