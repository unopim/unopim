<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Psr\Log\LoggerInterface;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\DataTransfer\Services\JobTypeGate;
use Webkul\User\Models\AdminProxy;

class ImportTrackBatch implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public $tries = 3;

    public $timeout = 0; // Adjust as needed

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
        // Set owner every job — a persistent worker retains a stale guard between jobs; finally clears it to avoid leaks.
        $user = AdminProxy::find($this->importBatch->user_id);

        if ($user) {
            auth()->guard('admin')->setUser($user);
        }

        try {
            $this->runImport();
        } finally {
            auth()->guard('admin')->forgetUser();
        }
    }

    /**
     * Run the import pipeline for the tracked batch.
     *
     * The track is refreshed first so that a duplicate or stale delivery cannot
     * restart a track that has already reached a terminal state.
     *
     * Validation can persist pending batches even when it fails or exceeds the
     * configured error limit, so the pipeline stops before scheduling them.
     */
    protected function runImport(): void
    {
        $this->importBatch->refresh();

        if (in_array($this->importBatch->state, [ImportHelper::STATE_FAILED, ImportHelper::STATE_CANCELLED, ImportHelper::STATE_COMPLETED], true)) {
            return;
        }

        $importHelper = resolve(ImportHelper::class);

        $importHelper->setImport($this->importBatch);

        $logger = JobLogger::make($this->importBatch->id);

        $importHelper->setLogger($logger);

        if ($this->importBatch instanceof JobTrack && app(JobTypeGate::class)->hidesTrack($this->importBatch)) {
            $this->refuseHiddenType($logger);

            return;
        }

        $logger->info(trans('data_transfer::app.job.started'));

        // Set started_at now so the tracker timer begins from the validation phase.
        $importHelper->stateUpdate(ImportHelper::STATE_VALIDATING, ['started_at' => now()]);

        $import = $importHelper->validate();

        $this->importBatch = $import->getImport();

        if ($import->isErrorLimitExceeded() || $this->importBatch->state === ImportHelper::STATE_FAILED) {
            $importHelper->stateUpdate(ImportHelper::STATE_FAILED, ['completed_at' => now()]);

            $logger->error(trans('data_transfer::app.job.stopped', [
                'count' => $this->importBatch->errors_count,
            ]));

            return;
        }

        if ($import->isValid() && $this->importBatch->state === ImportHelper::STATE_VALIDATED) {
            // Don't overwrite started_at — it was set at validation start
            $importHelper->started(preserveStartedAt: true);
        }

        $pendingBatch = $this->importBatch->batches->where('state', ImportHelper::STATE_PENDING)->first();

        if ($pendingBatch) {
            try {
                $importHelper->start(null, $this->queue);
            } catch (\Exception $e) {
                $this->importBatch->state = ImportHelper::STATE_FAILED;
                $this->importBatch->errors = [$e->getMessage()];
                $this->importBatch->save();

                $logger->error("Import process failed: {$e->getMessage()}", [
                    'exception' => $e->getTraceAsString(),
                ]);

                return;
            }
        } elseif ($importHelper->isLinkingRequired()) {
            $importHelper->linking();
        } else {
            $importHelper->completed();
        }

        $state = match ($this->importBatch->state) {
            ImportHelper::STATE_LINKING  => $importHelper->isIndexingRequired() ? ImportHelper::STATE_INDEXING : ImportHelper::STATE_COMPLETED,
            ImportHelper::STATE_INDEXING => ImportHelper::STATE_COMPLETED,
            default                      => ImportHelper::STATE_COMPLETED,
        };

        $importHelper->stats($state);
    }

    /**
     * Fail a track whose job type was switched off after it was queued.
     */
    protected function refuseHiddenType(LoggerInterface $logger): void
    {
        $message = trans('data_transfer::app.job.module-disabled', ['type' => $this->importBatch->jobInstance?->entity_type]);

        $logger->error($message);

        $this->importBatch->state = ImportHelper::STATE_FAILED;
        $this->importBatch->errors = [$message];
        $this->importBatch->save();
    }

    public function failed(\Throwable $exception): void
    {
        $logger = JobLogger::make($this->importBatch->id);

        $logger->error("ImportTrackBatch failed: {$exception->getMessage()}", [
            'exception' => $exception->getTraceAsString(),
        ]);

        $this->importBatch->state = ImportHelper::STATE_FAILED;
        $this->importBatch->errors = [$exception->getMessage()];
        $this->importBatch->save();
    }
}
