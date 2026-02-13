<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\Tenant\Jobs\TenantAwareJob;

class UploadFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     * @return void
     */
    public function __construct(
        protected $export,
        protected $filePath,
        protected $temporaryPath,
        protected $filters
    ) {
        $this->export = $export;
        $this->filePath = $filePath;
        $this->temporaryPath = $temporaryPath;
        $this->filters = $filters;
        $this->captureTenantContext();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(ExportHelper::class)
            ->setExport($this->export)
            ->uploadFile($this->filePath, $this->temporaryPath, $this->filters);
    }
}
