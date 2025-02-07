<?php

namespace Webkul\ElasticSearch\Console\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        if (config('elasticsearch.connection')) {
            $indexPrefix = config('elasticsearch.prefix');

            $start = microtime(true);

            $products = DB::table('products')->get();

            $productIndex = strtolower($indexPrefix.'_products');

            if ($products->isNotEmpty()) {
                $elasticProduct = $this->getProductUpdates($productIndex, $this);

                $this->info('Indexing products into Elasticsearch...');
                $dbProductIds = $products->pluck('id')->toArray();

                $progressBar = new ProgressBar($this->output, count($products));
                $progressBar->start();

                foreach ($products as $productDB) {
                    $product = new Product;

                    $product->forceFill((array) $productDB);
                    $product->syncOriginal();
                    if (
                        (
                            isset($elasticProduct[$product->id])
                            && $elasticProduct[$product->id] != Carbon::parse($product->updated_at)->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z')
                        )
                        || ! isset($elasticProduct[$product->id])
                    ) {
                        
                        $productData = $product->toArray();
                        $productData['values'] = json_decode($productData['values'], true);
                        
                        Elasticsearch::index([
                            'index' => $productIndex,
                            'id'    => $product->id,
                            'body'  => $productData,
                        ]);
                    }
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
                $this->info('Product indexing completed.');

                Log::channel('elasticsearch')->info('Product indexing completed.');

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

                    Log::channel('elasticsearch')->info('Stale products deleted successfully.');
                } else {
                    $this->info('No stale products to delete.');

                    Log::channel('elasticsearch')->info('No stale products to delete.');
                }
            } else {
                $this->info('No product found in the database. Attempting to delete the index if it exists:-');
                Log::channel('elasticsearch')->info('No product found in the database. Attempting to delete the index if it exists:-');

                try {
                    Elasticsearch::indices()->delete(['index' => $productIndex]);
                    $this->info($productIndex.' index deleted successfully.');

                    Log::channel('elasticsearch')->info($productIndex.' index deleted successfully.');
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                        $this->warn('Index not found: '.$productIndex);

                        Log::channel('elasticsearch')->warning($productIndex.' index not found: ', [
                            'warning' => $e->getMessage(),
                        ]);
                    } else {
                        Log::channel('elasticsearch')->error('Exception while clearing '.$productIndex.' index: ', [
                            'error' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }
            }

            $end = microtime(true);

            $this->info('The operation took '.round($end - $start, 4).' seconds to complete.');
        } else {
            $this->warn('ELASTICSEARCH IS NOT ENABLED.');

            Log::channel('elasticsearch')->warning('ELASTICSEARCH IS NOT ENABLE.');
        }
    }

    public function getProductUpdates($productIndex, $command = null)
    {
        $scrollSize = 10000;
        $elasticProduct = [];

        try {
            $response = Elasticsearch::search([
                'index' => $productIndex,
                'body'  => [
                    '_source' => ['updated_at'],
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                    'size' => $scrollSize,
                ],
                'scroll' => '10m',
            ]);

            $scrollId = $response['_scroll_id'];

            foreach ($response['hits']['hits'] as $hit) {
                $elasticProduct[(int) $hit['_id']] = $hit['_source']['updated_at'];
            }

            while (true) {
                $response = Elasticsearch::scroll([
                    'scroll_id' => $scrollId,
                    'scroll'    => '10m',
                ]);

                if (empty($response['hits']['hits'])) {
                    break;
                }

                foreach ($response['hits']['hits'] as $hit) {
                    $elasticProduct[(int) $hit['_id']] = $hit['_source']['updated_at'];
                }
            }

            try {
                Elasticsearch::clearScroll([
                    'scroll_id' => $scrollId,
                ]);
            } catch (\Exception $e) {
                Log::channel('elasticsearch')->error('Exception while clearing scroll: ', [
                    'error' => $e->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                if ($command) {
                    $command->info('No data found. Initiating fresh indexing');
                }
                Log::channel('elasticsearch')->info('No data found. Initiating fresh indexing');
            } else {
                Log::channel('elasticsearch')->error('Exception while fetching '.$productIndex.' index: ', [
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        return $elasticProduct;
    }
}
