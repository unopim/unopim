<?php

namespace Webkul\AiAgent\Examples;

use Illuminate\Console\Command;
use Webkul\AiAgent\Agents\BulkProductEnricherAgent;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Example console command for bulk enriching products.
 *
 * Shows batch processing with agents, database integration,
 * and progress tracking.
 *
 * Usage:
 *   php artisan ai-agent:enrich-products --limit=100 --agent-id=4 --credential-id=1
 */
class EnrichProductsBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-agent:enrich-products {--limit=50} {--agent-id=4} {--credential-id=1} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk enrich product data using AI Agent';

    /**
     * Execute the console command.
     */
    public function handle(
        BulkProductEnricherAgent $enricher,
        ProductRepository $productRepository,
    ): int {
        $limit = (int) $this->option('limit');
        $agentId = (int) $this->option('agent-id');
        $credentialId = (int) $this->option('credential-id');
        $dryRun = $this->option('dry-run');

        $this->info("Fetching up to $limit products for enrichment...");

        // Fetch products
        $products = $productRepository->all()
            ->take($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->warn('No products found.');

            return self::SUCCESS;
        }

        $this->info('Processing '.$products->count().' products...');

        // Convert to array format for agent
        $productData = $products->map(fn ($p) => [
            'sku'         => $p->sku,
            'name'        => $p->name,
            'description' => $p->description,
            'category'    => $p->category?->name,
        ])->toArray();

        // Run enrichment
        $bar = $this->output->createProgressBar(1);
        $result = $enricher->enrichBatch(
            products: $productData,
            agentId: $agentId,
            credentialId: $credentialId,
        );
        $bar->finish();

        $this->newLine();

        if (! $result->success) {
            $this->error('❌ Enrichment failed: '.$result->error);

            return self::FAILURE;
        }

        $this->info('✅ Enrichment complete.');

        if ($dryRun) {
            $this->info('(Dry run — no changes persisted)');
        } else {
            $this->info('Updates persisted to database.');
        }

        // Display sample results
        $this->displayResults($result->data);

        return self::SUCCESS;
    }

    /**
     * Display enrichment results in a table.
     *
     * @param  array<mixed>  $data
     */
    protected function displayResults(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $this->table(
            ['SKU', 'Name', 'Category', 'Quality Score'],
            array_map(fn ($item) => [
                $item['sku'] ?? '—',
                $item['name'] ?? '—',
                $item['category'] ?? '—',
                ($item['qualityScore'] ?? 0).'%',
            ], array_slice($data, 0, 5)),
        );

        if (count($data) > 5) {
            $this->info('... and '.(count($data) - 5).' more.');
        }
    }
}
