<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class ImportBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $importBatch
     * @return void
     */
    public function __construct(protected $importBatch, protected $jobTrackId) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $typeImported = app(ImportHelper::class)
            ->setImport($this->importBatch->jobTrack)
            ->setLogger(JobLogger::make($this->jobTrackId))
            ->getTypeImporter();

        $typeImported->importBatch($this->importBatch);
    }

    public function failed(\Throwable $exception)
    {
        JobLogger::make($this->jobTrackId)->error($exception->getMessage(), [
            'batch_id' => $this->importBatch->id,
        ]);

        $this->importBatch->state = 'failed';
        $this->importBatch->save();
    }
}
