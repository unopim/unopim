<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Completed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     */
    public function __construct(
        protected mixed $export,
        protected mixed $jobTrackId,
        protected mixed $exportBuffer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exportHelper = app(ExportHelper::class)
            ->setExport($this->export)
            ->setLogger(JobLogger::make($this->jobTrackId));

        if ($exportHelper->shouldStop()) {
            JobLogger::make($this->jobTrackId)->info('Export Completed job skipped — export was stopped.');

            return;
        }

        $exportHelper
            ->flush($this->exportBuffer)
            ->completed();

        Cache::forget('export_init_'.$this->export->id);
    }

    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->jobTrackId)->error("Export Completed job failed: {$exception->getMessage()}", [
            'exception' => $exception->getTraceAsString(),
        ]);
    }
}
