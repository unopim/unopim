<?php

namespace Webkul\AiAgent\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\AiAgent\Jobs\IndexProductEmbeddingsJob;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;

/**
 * (Re)indexes product embeddings into the persistent vector store in queued batches.
 *
 * Run via: php artisan ai-agent:embeddings:index [--since=2026-01-01] [--batch=100]
 */
class IndexProductEmbeddings extends Command
{
    protected $signature = 'ai-agent:embeddings:index
                            {--since= : Only queue products updated at or after this date/time}
                            {--batch= : Products per queued job batch (defaults to config batch_size)}';

    protected $description = 'Queue (re)indexing of product embeddings into the AI vector store';

    public function __construct(protected ProductEmbeddingIndex $productEmbeddingIndex)
    {
        parent::__construct();
    }

    /**
     * Queue embedding index jobs in id-ordered batches (resumable, 500K-safe).
     */
    public function handle(): int
    {
        if (! $this->productEmbeddingIndex->isEnabled()) {
            $this->warn('The AI vector store is disabled. Enable AI_AGENT_VECTOR_STORE_ENABLED and ELASTICSEARCH_ENABLED first.');

            return self::SUCCESS;
        }

        $since = null;

        if ($this->option('since')) {
            try {
                $since = Carbon::parse($this->option('since'));
            } catch (\Throwable) {
                $this->error('Invalid --since value. Provide a parseable date/time, e.g. 2026-01-01 or "2026-01-01 10:00:00".');

                return self::FAILURE;
            }
        }

        $batchSize = (int) ($this->option('batch') ?: config('ai-agent.vector_store.batch_size', 100));
        $batchSize = max(1, min($batchSize, 1000));

        $this->productEmbeddingIndex->ensureIndex();

        $query = DB::table('products')
            ->select('id')
            ->when($since, fn ($query) => $query->where('updated_at', '>=', $since))
            ->orderBy('id');

        $queuedProducts = 0;
        $queuedJobs = 0;

        $query->chunkById($batchSize, function ($products) use (&$queuedProducts, &$queuedJobs) {
            $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();

            IndexProductEmbeddingsJob::dispatch($productIds);

            $queuedProducts += count($productIds);
            $queuedJobs++;
        });

        $this->info("Queued {$queuedJobs} embedding job(s) covering {$queuedProducts} product(s).");
        $this->info('Run a queue worker (php artisan queue:work) to process them.');

        return self::SUCCESS;
    }
}
