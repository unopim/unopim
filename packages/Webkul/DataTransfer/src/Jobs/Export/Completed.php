<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Completed implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected $export,
        protected $jobTrackId,
        protected $exportBuffer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportHelper = resolve(ExportHelper::class)
            ->setExport($this->export)
            ->setLogger(JobLogger::make($this->jobTrackId));

        if ($exportHelper->shouldStop()) {
            JobLogger::make($this->jobTrackId)->info('Export Completed job skipped — export was stopped.');

            return;
        }

        $exportHelper
            ->flush($this->exportBuffer)
            ->completed();

        if ($this->exportBuffer && method_exists($this->exportBuffer, 'delete')) {
            $this->exportBuffer->delete();
        }

        Cache::forget('export_init_'.$this->export->id);
    }

    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->jobTrackId)->error("Export Completed job failed: {$exception->getMessage()}", [
            'exception' => $exception->getTraceAsString(),
        ]);
    }
}
