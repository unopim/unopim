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
use Webkul\User\Models\AdminProxy;

class ImportTrackBatch implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $importBatch;

    public $tries = 3;

    public $timeout = 0; // Adjust as needed

    /**
     * Create a new job instance.
     *
     * @param  mixed  $importBatch
     * @return void
     */
    public function __construct($importBatch)
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
        if (! auth()->guard('admin')->check()) {
            $user = AdminProxy::find($this->importBatch->user_id);
            auth('admin')->login($user);
        }

        $importHelper = app(ImportHelper::class);

        $importHelper->setImport($this->importBatch);

        $logger = JobLogger::make($this->importBatch->id);

        $importHelper->setLogger($logger);

        $logger->info(trans('data_transfer::app.job.started'));

        // Validate the import
        $import = $importHelper->validate();

        $this->importBatch = $import->getImport();

        if ($import->isValid() && $this->importBatch->state === ImportHelper::STATE_VALIDATED) {
            $importHelper->started();
        }

        // Check for pending batches
        $pendingBatch = $this->importBatch->batches->where('state', ImportHelper::STATE_PENDING)->first();

        if ($pendingBatch) {
            // Start the import process
            try {
                $importHelper->start(null, $this->queue);
            } catch (\Exception $e) {
                $this->importBatch->state = ImportHelper::STATE_FAILED;
                $this->importBatch->errors = [$e->getMessage()];
                $this->importBatch->save();

                \Log::error('Import process failed: '.$e->getMessage());

                return;
            }
        } else {
            // Handle linking or indexing if required
            if ($importHelper->isLinkingRequired()) {
                $importHelper->linking();
            } else {
                $importHelper->completed();
            }
        }

        // Determine final state based on current state
        $state = match ($this->importBatch->state) {
            ImportHelper::STATE_LINKING  => $importHelper->isIndexingRequired() ? ImportHelper::STATE_INDEXING : ImportHelper::STATE_COMPLETED,
            ImportHelper::STATE_INDEXING => ImportHelper::STATE_COMPLETED,
            default                      => ImportHelper::STATE_COMPLETED,
        };

        // Gather stats
        $stats = $importHelper->stats($state);
    }
}
