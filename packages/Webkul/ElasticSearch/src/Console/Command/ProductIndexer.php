<?php

namespace Webkul\ElasticSearch\Console\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer;
use Webkul\Product\Models\Product;

class ProductIndexer extends Command
{
    const BATCH_SIZE = 10000;

    protected $signature = 'unopim:product:index';

    protected $description = 'Index all products into Elasticsearch';

    public function __construct(protected ProductNormalizer $productIndexingNormalizer)
    {
        parent::__construct();
    }

    public function handle()
    {
        if (config('elasticsearch.enabled')) {
            $indexPrefix = config('elasticsearch.prefix');

            $start = microtime(true);

            $productIndex = strtolower($indexPrefix.'_products');

            $totalProducts = DB::table('products')->count();

            if ($totalProducts === 0) {
                $this->info('No products found in the database. Attempting to reset the index if it exists.');
                Log::channel('elasticsearch')->info('No products found in the database. Attempting to reset the index if it exists.');

                try {
                    ElasticSearch::indices()->delete(['index' => $productIndex]);
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

            if (! $this->hasIndex($productIndex)) {
                $this->elasticConfiguration($productIndex);
            }

            $progressBar = new ProgressBar($this->output, $totalProducts);

            $dbProductIds = [];
            $failedProductIds = [];

            for ($offset = 0; $offset < $totalProducts; $offset += self::BATCH_SIZE) {
                $products = DB::table('products')->offset($offset)->limit(self::BATCH_SIZE)->get();

                $elasticProduct = $this->getProductUpdates($productIndex, $this, $products->pluck('id')->toArray());

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
                            if (! empty($product->values)) {
                                $product->values = $this->productIndexingNormalizer->normalize($product->values);
                            }

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
                        $response = ElasticSearch::bulk($productsToUpdate);

                        if (isset($response['errors']) && $response['errors']) {
                            foreach ($response['items'] as $index => $result) {
                                if (isset($result['index']['error'])) {
                                    $failedProductIds[] = $result['index']['_id'];

                                    Log::channel('elasticsearch')->error('Error while indexing product id: '.$result['index']['_id'].' in '.$productIndex.' index: ', [
                                        'error' => $result['index']['error'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            if (! empty($failedProductIds)) {
                $this->newLine();
                $this->error('Please check elasticsearch.log, failed to index the following product IDs: '.implode(', ', $failedProductIds));
            }

            $progressBar->finish();
            $this->newLine();
            $this->info('Product indexing completed.');

            Log::channel('elasticsearch')->info('Product indexing completed.');

            $this->info('Checking for stale products to delete...');

            $elasticProductIds = collect(ElasticSearch::search([
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
                        ElasticSearch::bulk($deleteProducts);
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

    public function getProductUpdates($productIndex, $command = null, array $searchIds = [])
    {
        $elasticProduct = [];

        try {
            $response = ElasticSearch::search([
                'index' => $productIndex,
                'body'  => [
                    '_source' => ['updated_at'],
                    'query'   => [
                        'ids' => [
                            'values' => $searchIds,
                        ],
                    ],
                    'size' => self::BATCH_SIZE,
                ],
            ]);

            foreach ($response['hits']['hits'] as $hit) {
                $elasticProduct[(int) $hit['_id']] = $hit['_source']['updated_at'];
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

    public function hasIndex($productIndex): bool
    {
        return ElasticSearch::indices()->exists(['index' => $productIndex])->asBool();
    }

    public function elasticConfiguration($productIndex)
    {
        try {
            ElasticSearch::indices()->create([
                'index' => $productIndex,
                'body'  => [
                    'settings' => $this->getUnopimProductSetting(),
                    'mappings' => $this->getUnopimProductMapping(),
                ],
            ]);

            $this->info($productIndex.' index recreated successfully.');
            Log::channel('elasticsearch')->info($productIndex.' index recreated successfully.');
        } catch (\Exception $e) {
            Log::channel('elasticsearch')->error('Exception while recreating '.$productIndex.' index.', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getUnopimProductMapping()
    {
        return [
            'properties' => [
                'attribute_family' => [
                    'properties' => [
                        'code'         => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                        'id'           => ['type' => 'long'],
                        'name'         => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                        'status'       => ['type' => 'long'],
                        'translations' => [
                            'properties' => [
                                'attribute_family_id' => ['type' => 'long'],
                                'id'                  => ['type' => 'long'],
                                'locale'              => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                                'name'                => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                            ],
                        ],
                    ],
                ],
                'attribute_family_id' => ['type' => 'long'],
                'created_at'          => ['type' => 'date'],
                'id'                  => ['type' => 'long'],
                'sku'                 => [
                    'type'       => 'keyword',
                    'normalizer' => 'sku_normalizer',
                ],
                'status'     => ['type' => 'long'],
                'type'       => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                'updated_at' => ['type' => 'date'],
            ],
            'dynamic_templates' => $this->dynamicAttributeMappings(),
        ];
    }

    protected function dynamicAttributeMappings()
    {
        $attributeTypes = [
            'text'     => 'text',
            'textarea' => 'text',
            'price'    => 'float',
            'datetime' => 'date',
            'date'     => 'date',
        ];

        $scopes = [
            'common'                  => 'values.common',
            'locale_specific'         => 'values.locale_specific.*',
            'channel_specific'        => 'values.channel_specific.*',
            'channel_locale_specific' => 'values.channel_locale_specific.*.*',
        ];

        $dynamicTemplates = [];

        foreach ($scopes as $scope => $path) {
            $dynamicTemplates[] = [
                "object_fields_{$scope}" => [
                    'path_match'         => $path.'.*',
                    'match_mapping_type' => 'object',
                    'mapping'            => ['type' => 'object'],
                ],
            ];
        }

        foreach ($attributeTypes as $attributeType => $esType) {
            foreach ($scopes as $scope => $path) {
                $matchPath = $path.".*-{$attributeType}";

                $mapping = ['type' => $esType];

                if ($attributeType === 'price') {
                    $matchPath = $path.".*-{$attributeType}.*";
                }

                if ($esType === 'text') {
                    $mapping['fields'] = [
                        'keyword' => ['type' => 'keyword', 'normalizer' => 'string_normalizer'],
                    ];
                }

                if ($esType === 'keyword') {
                    $mapping['normalizer'] = 'string_normalizer';
                }

                if ($attributeType === 'date') {
                    $mapping['format'] = 'yyyy-MM-dd';
                }

                if ($attributeType === 'datetime') {
                    $mapping['format'] = 'yyyy-MM-dd HH:mm:ss';
                }

                $dynamicTemplates[] = [
                    "{$attributeType}_fields_{$scope}" => [
                        'path_match' => $matchPath,
                        'mapping'    => $mapping,
                    ],
                ];
            }
        }

        // Map default as keyword for all values
        foreach ($scopes as $scope => $path) {
            $dynamicTemplates[] = [
                "fallback_fields_{$scope}" => [
                    'path_match'         => $path.'.*',
                    'match_mapping_type' => 'string',
                    'mapping'            => ['type' => 'keyword'],
                ],
            ];
        }

        $dynamicTemplates[] = [
            'fallback_object' => [
                'path_match'         => 'values.*',
                'match_mapping_type' => 'object',
                'mapping'            => ['type' => 'object'],
            ],
        ];

        return $dynamicTemplates;
    }

    private function getUnopimProductSetting()
    {
        return [
            'analysis' => [
                'char_filter' => [
                    'newline_remover' => [
                        'type'     => 'mapping',
                        'mappings' => ['\\n => '],
                    ],
                ],
                'analyzer' => [
                    'my_analyzer' => [
                        'tokenizer' => 'standard',
                        'filter'    => ['lowercase'],
                    ],
                ],
                'normalizer' => [
                    'sku_normalizer' => [
                        'filter' => ['lowercase'],
                    ],

                    'string_normalizer' => [
                        'char_filter' => ['newline_remover'],
                        'filter'      => ['lowercase'],
                    ],
                    'url_normalizer' => [
                        'type'   => 'custom',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
            ],
        ];
    }
}
