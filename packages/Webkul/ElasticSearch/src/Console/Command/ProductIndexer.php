<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;
use Symfony\Component\Console\Helper\ProgressBar;

class ProductIndexer extends Command
{
    protected $signature = 'product:index';

    protected $description = 'Index all products into Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            $indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');

            $start = microtime(true);

            $products = Product::all();

            if ($products->isNotEmpty()) {
                $productIndex = strtolower($indexPrefix.'_products');

                $dbProductIds = $products->pluck('id')->toArray();

                $this->info('Indexing products into Elasticsearch...');
                $progressBar = new ProgressBar($this->output, count($products));
                $progressBar->start();

                foreach ($products as $product) {
                    Elasticsearch::index([
                        'index' => $productIndex,
                        'id'    => $product->id,
                        'body'  => $product->toArray(),
                    ]);
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
                $this->info('Product indexing completed.');

                $this->info('Checking for stale products to delete...');
                $elasticProductIds = collect(Elasticsearch::search([
                    'index' => $productIndex,
                    'body'  => [
                        '_source' => false,
                        'query'   => [
                            'match_all' => new \stdClass,
                        ],
                    ],
                ])['hits']['hits'])->pluck('_id')->map(fn($id) => (int) $id)->toArray();

                $productsToDelete = array_diff($elasticProductIds, $dbProductIds);

                if (!empty($productsToDelete)) {
                    $this->info('Deleting stale products from Elasticsearch...');
                    $deleteProgressBar = new ProgressBar($this->output, count($productsToDelete));
                    $deleteProgressBar->start();

                    foreach ($productsToDelete as $productId) {
                        Elasticsearch::delete([
                            'index' => $productIndex,
                            'id'    => $productId,
                        ]);
                        $deleteProgressBar->advance();
                    }

                    $deleteProgressBar->finish();
                    $this->newLine();
                    $this->info('Stale products deleted successfully.');
                } else {
                    $this->info('No stale products to delete.');
                }
            } else {
                $this->info('No products found.');
            }

            $end = microtime(true);

            $this->info('The operation took ' . round($end - $start, 4) . ' seconds to complete.');
        } else {
            $this->warn('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
