<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;

class UploadFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     */
    public function __construct(protected mixed $export, protected mixed $filePath, protected mixed $temporaryPath, protected mixed $filters) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(ExportHelper::class)
            ->setExport($this->export)
            ->uploadFile($this->filePath, $this->temporaryPath, $this->filters);
    }
}
