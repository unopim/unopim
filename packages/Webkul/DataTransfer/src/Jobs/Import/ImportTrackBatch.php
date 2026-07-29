<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;
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
     */
    protected function runImport(): void
    {
        $importHelper = resolve(ImportHelper::class);

        $importHelper->setImport($this->importBatch);

        $logger = JobLogger::make($this->importBatch->id);

        $importHelper->setLogger($logger);

        $logger->info(trans('data_transfer::app.job.started'));

        // Set started_at now so the tracker timer begins from the validation phase.
        $importHelper->stateUpdate(ImportHelper::STATE_VALIDATING, ['started_at' => now()]);

        $import = $importHelper->validate();

        $this->importBatch = $import->getImport();

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
