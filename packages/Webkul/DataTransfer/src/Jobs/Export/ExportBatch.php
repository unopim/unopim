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

    public $tries = 3;

    /**
     * Per-batch timeout in seconds. Scaled with the batch size (with the previous fixed 600s as a
     * floor) so media-heavy or very large batches are not killed mid-write on big exports.
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $exportBatch
     * @return void
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
     *
     * @return void
     */
    public function handle()
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

    public function failed(\Throwable $exception)
    {
        JobLogger::make($this->jobTrackId)->error("ExportBatch #{$this->exportBatch->id} failed: {$exception->getMessage()}", [
            'batch_id'  => $this->exportBatch->id,
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->exportBatch->state = 'failed';
        $this->exportBatch->save();
    }
}
