<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Completed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     * @return void
     */
    public function __construct(protected $import, protected $jobTrackId) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(ImportHelper::class)
            ->setImport($this->import)
            ->setLogger(JobLogger::make($this->jobTrackId))
            ->completed();

        $this->dispatchPostImportCompleteness();
    }

    /**
     * Dispatch a single completeness job on the dedicated completeness queue
     * after ALL import batches have finished. This decouples completeness
     * from the import pipeline — a separate worker can consume it independently.
     *
     * Only fires for product imports (entity_type === 'products').
     */
    protected function dispatchPostImportCompleteness(): void
    {
        if ($this->import->jobInstance->entity_type !== 'products') {
            return;
        }

        if ($this->import->action === ImportHelper::ACTION_DELETE) {
            return;
        }

        // Collect all SKUs from all processed batches in a single query
        $skus = $this->import->batches()
            ->where('state', ImportHelper::STATE_PROCESSED)
            ->get('data')
            ->flatMap(fn ($batch) => array_column($batch->data ?? [], 'sku'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($skus)) {
            return;
        }

        // Resolve product IDs from SKUs in one query
        $ids = DB::table('products')
            ->whereIn('sku', $skus)
            ->pluck('id')
            ->toArray();

        if (! empty($ids)) {
            BulkProductCompletenessJob::dispatch($ids);
        }
    }
}
