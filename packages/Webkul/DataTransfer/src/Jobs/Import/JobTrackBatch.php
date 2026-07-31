<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;

class JobTrackBatch implements ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $importBatch
     */
    public function __construct(protected $importBatch) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $typeImported = resolve(ImportHelper::class)
            ->setImport($this->importBatch->import)
            ->getTypeImporter();

        $typeImported->importBatch($this->importBatch);
    }
}
