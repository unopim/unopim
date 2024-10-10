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

    /**
     * Create a new job instance.
     *
     * @param  mixed  $exportBatch
     * @return void
     */
    public function __construct(
        protected $exportBatch,
        protected $filePath,
        protected $jobTrackId
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $typeExported = app(ExportHelper::class)
            ->setExport($this->exportBatch->jobTrack)
            ->setLogger(JobLogger::make($this->jobTrackId))
            ->getTypeExporter();

        $typeExported->exportBatch($this->exportBatch, $this->filePath);
    }
}
