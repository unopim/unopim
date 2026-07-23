<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class LinkBatch implements ShouldQueue
{
    use Batchable, \Illuminate\Foundation\Queue\Queueable;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $importBatch
     */
    public function __construct(protected $importBatch, protected $jobTrackId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = JobLogger::make($this->jobTrackId);

        $importHelper = resolve(ImportHelper::class)
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

    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->jobTrackId)->error("LinkBatch #{$this->importBatch->id} failed: {$exception->getMessage()}", [
            'batch_id'  => $this->importBatch->id,
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->importBatch->state = 'failed';
        $this->importBatch->save();

        $jobTrack = $this->importBatch->jobTrack;

        if ($jobTrack && $jobTrack->state !== ImportHelper::STATE_FAILED) {
            $jobTrack->state = ImportHelper::STATE_FAILED;
            $jobTrack->completed_at = now();
            $jobTrack->save();
        }
    }
}
