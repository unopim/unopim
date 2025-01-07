<?php

namespace Webkul\ElasticSearch\Console\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

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

            $productIndex = strtolower($indexPrefix.'_products');

            if ($products->isNotEmpty()) {
                $elasticProduct = [];

                try {
                    $elasticProduct = collect(Elasticsearch::search([
                        'index' => $productIndex,
                        'body'  => [
                            '_source' => ['updated_at'],
                            'query'   => [
                                'match_all' => new \stdClass,
                            ],
                            'size' => 1000000000,
                        ],
                    ])['hits']['hits'])->mapWithKeys(function ($hit) {
                        return [(int) $hit['_id'] => $hit['_source']['updated_at']];
                    })->toArray();
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->info('No data found. Initiating fresh indexing');
                    } else {
                        throw $e;
                    }
                }

                $this->info('Indexing products into Elasticsearch...');
                $dbProductIds = $products->pluck('id')->toArray();

                $progressBar = new ProgressBar($this->output, count($products));
                $progressBar->start();

                foreach ($products as $product) {
                    if (
                        (
                            isset($elasticProduct[$product->id])
                            && $elasticProduct[$product->id] != Carbon::parse($product->updated_at)->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z')
                        )
                        || ! isset($elasticProduct[$product->id])
                    ) {
                        Elasticsearch::index([
                            'index' => $productIndex,
                            'id'    => $product->id,
                            'body'  => $product->toArray(),
                        ]);
                    }
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
                        'size' => 1000000000,
                    ],
                ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

                $productsToDelete = array_diff($elasticProductIds, $dbProductIds);

                if (! empty($productsToDelete)) {
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
                try {
                    Elasticsearch::indices()->delete(['index' => $productIndex]);
                    $this->info('Elasticsearch index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$productIndex);
                    } else {
                        throw $e;
                    }
                }
            }

            $end = microtime(true);

            $this->info('The operation took '.round($end - $start, 4).' seconds to complete.');
        } else {
            $this->warn('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
