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
    const BATCH_SIZE = 10000;

    protected $signature = 'product:index';

    protected $description = 'Index all products into Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (config('elasticsearch.connection')) {
            $indexPrefix = config('elasticsearch.prefix') ? config('elasticsearch.prefix') : config('app.name');

            $start = microtime(true);

            $productIndex = strtolower($indexPrefix.'_products');

            $totalProducts = DB::table('products')->count();

            if ($totalProducts === 0) {
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

                return;
            }

            $progressBar = new ProgressBar($this->output, $totalProducts);

            $dbProductIds = [];
            $elasticProduct = $this->getProductUpdates($productIndex, $this);

            for ($offset = 0; $offset < $totalProducts; $offset += self::BATCH_SIZE) {
                $products = DB::table('products')->offset($offset)->limit(self::BATCH_SIZE)->get();

                if ($products->isNotEmpty()) {
                    if ($offset === 0) {
                        $this->info('Indexing products into Elasticsearch...');

                        $progressBar->start();
                    }

                    $productsToUpdate = [];

                    foreach ($products as $productDB) {
                        $product = new Product;

                        $productDB = (array) $productDB;

                        $productDB['values'] = is_string($productDB['values']) ? json_decode($productDB['values'], true) : $productDB['values'];

                        $product->forceFill($productDB);
                        $product->syncOriginal();

                        $productId = $product->id;

                        $dbProductIds[] = $productId;

                        if (
                            (
                                isset($elasticProduct[$productId])
                                && $elasticProduct[$productId] != Carbon::parse($product->updated_at)->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z')
                            )
                            || ! isset($elasticProduct[$productId])
                        ) {
                            $productsToUpdate['body'][] = [
                                'index' => [
                                    '_index' => $productIndex,
                                    '_id'    => $productId,
                                ],
                            ];

                            $productsToUpdate['body'][] = $product->toArray();
                        }

                        $progressBar->advance();
                    }

                    if ($productsToUpdate) {
                        Elasticsearch::bulk($productsToUpdate);
                    }
                }
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

                $productChunks = array_chunk($productsToDelete, self::BATCH_SIZE);

                foreach ($productChunks as $chunk) {
                    $deleteProducts = [];

                    foreach ($chunk as $productId) {
                        $deleteProducts['body'][] = [
                            'delete' => [
                                '_index' => $productIndex,
                                '_id'    => $productId,
                            ],
                        ];

                        $deleteProgressBar->advance();
                    }

                    if ($deleteProducts) {
                        Elasticsearch::bulk($deleteProducts);
                    }
                }

                $deleteProgressBar->finish();
                $this->newLine();
                $this->info('Stale products deleted successfully.');

                Log::channel('elasticsearch')->info('Stale products deleted successfully.');
            } else {
                $this->info('No stale products to delete.');

                Log::channel('elasticsearch')->info('No stale products to delete.');
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
