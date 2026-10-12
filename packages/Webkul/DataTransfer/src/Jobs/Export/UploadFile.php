<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;

class UploadFile implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $export, protected $filePath, protected $temporaryPath, protected $filters) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        resolve(ExportHelper::class)
            ->setExport($this->export)
            ->uploadFile($this->filePath, $this->temporaryPath, $this->filters);
    }
}
