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
use Webkul\Tenant\Jobs\TenantAwareJob;

class ExportBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

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
        $this->captureTenantContext();
    }

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
            ->setExportBuffer($this->exportBuffer)
            ->getTypeExporter();

        $typeExported->exportBatch($this->exportBatch, $this->filePath);
    }
}
