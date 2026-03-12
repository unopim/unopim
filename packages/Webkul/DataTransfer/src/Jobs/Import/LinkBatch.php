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

class LinkBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $importBatch
     * @return void
     */
    public function __construct(protected $importBatch, protected $jobTrackId)
    {
        $this->importBatch = $importBatch;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logger = JobLogger::make($this->jobTrackId);

        $importHelper = app(ImportHelper::class)
            ->setImport($this->importBatch->jobTrack)
            ->setLogger($logger);

        if ($importHelper->shouldStop()) {
            $logger->info("LinkBatch #{$this->importBatch->id} skipped — import was stopped.");

            $this->batch()?->cancel();

            return;
        }

        $logger->info("LinkBatch #{$this->importBatch->id} started processing.");

        $importHelper->getTypeImporter()->linkBatch($this->importBatch);

        $logger->info("LinkBatch #{$this->importBatch->id} completed.");
    }

    public function failed(\Throwable $exception)
    {
        JobLogger::make($this->jobTrackId)->error("LinkBatch #{$this->importBatch->id} failed: {$exception->getMessage()}", [
            'batch_id'  => $this->importBatch->id,
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->importBatch->state = 'failed';
        $this->importBatch->save();
    }
}
