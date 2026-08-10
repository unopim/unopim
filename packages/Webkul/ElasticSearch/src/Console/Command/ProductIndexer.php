<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer;
use Webkul\Product\Models\Product;

#[Description('Index all products into Elasticsearch')]
#[Signature('unopim:product:index
                            {--fresh : Drop the index and rebuild, skipping the per-document freshness comparison}
                            {--workers=1 : Index disjoint id ranges in parallel processes}')]
class ProductIndexer extends Command
{
    const BATCH_SIZE = 10000;

    /**
     * Documents per bulk request. A batch sent whole exceeds the coordinating
     * node's indexing pressure limit and comes back as a 429, which aborts the
     * run partway through the catalog.
     */
    const BULK_DOCUMENTS = 500;

    public function __construct(protected ProductNormalizer $productIndexingNormalizer)
    {
        parent::__construct();
    }

    public function handle(): void
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

                if (! $this->hasIndex($productIndex)) {
                    $this->elasticConfiguration($productIndex);
                }

                return;
            }

            if (! $this->hasIndex($productIndex)) {
                $this->elasticConfiguration($productIndex);
            }

            $fresh = (bool) $this->option('fresh');
            $workers = max(1, (int) $this->option('workers'));

            if ($fresh) {
                $this->recreateIndex($productIndex);
            }

            if ($workers > 1) {
                $this->info(sprintf('Indexing %s products across %d workers...', number_format($totalProducts), $workers));

                $this->indexInParallel($productIndex, $workers, $fresh);

                $this->info('Product indexing completed.');
                Log::channel('elasticsearch')->info('Product indexing completed.');

                if (! $fresh) {
                    $this->pruneStaleDocuments($productIndex);
                }

                $this->info('The operation took '.round(microtime(true) - $start, 4).' seconds to complete.');

                return;
            }

            $progressBar = new ProgressBar($this->output, $totalProducts);

            $failedProductIds = $this->indexRange($productIndex, null, null, $fresh, $progressBar);

            if ($failedProductIds !== []) {
                $this->newLine();
                $this->error('Please check elasticsearch.log, failed to index the following product IDs: '.implode(', ', $failedProductIds));
            }

            $progressBar->finish();
            $this->newLine();
            $this->info('Product indexing completed.');

            Log::channel('elasticsearch')->info('Product indexing completed.');

            if (! $fresh) {
                $this->pruneStaleDocuments($productIndex);
            }

            $this->info('The operation took '.round(microtime(true) - $start, 4).' seconds to complete.');

            return;
        }

        $this->warn('ELASTICSEARCH IS DISABLED.');

        Log::channel('elasticsearch')->warning('ELASTICSEARCH IS DISABLED.');
    }

    /**
     * Walk a contiguous id range by keyset, indexing each batch. OFFSET
     * re-scans every preceding row per page, and without an ORDER BY its page
     * boundaries are unstable — rows get skipped or indexed twice.
     *
     * @return array<int, string> ids that Elasticsearch rejected
     */
    protected function indexRange(
        string $productIndex,
        ?int $lowId,
        ?int $highId,
        bool $fresh,
        ?ProgressBar $progressBar = null,
    ): array {
        $failedProductIds = [];
        $lastId = ($lowId ?? 1) - 1;

        while (true) {
            $query = DB::table('products')->where('id', '>', $lastId)->orderBy('id')->limit(self::BATCH_SIZE);

            if ($highId !== null) {
                $query->where('id', '<=', $highId);
            }

            $products = $query->get();

            if ($products->isEmpty()) {
                break;
            }

            $lastId = (int) $products->last()->id;

            $elasticProduct = $fresh
                ? []
                : $this->getProductUpdates($productIndex, null, $products->pluck('id')->toArray());

            $productsToUpdate = [];
            $payloadByProductId = [];

            foreach ($products as $productDB) {
                $product = new Product;

                $productDB = (array) $productDB;

                $productDB['values'] = is_string($productDB['values']) ? json_decode($productDB['values'], true) : $productDB['values'];

                $product->forceFill($productDB);
                $product->syncOriginal();

                $productId = $product->id;

                if (
                    (
                        isset($elasticProduct[$productId])
                        && $elasticProduct[$productId] != Date::parse($product->updated_at)->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z')
                    )
                    || ! isset($elasticProduct[$productId])
                ) {
                    if (! empty($product->values)) {
                        $product->values = $this->productIndexingNormalizer->normalize($product->values);
                    }

                    $product = $product->toArray();

                    $product['status'] = (bool) ($product['status'] ?? true);
                    if (isset($product['attribute_family']['status'])) {
                        $product['attribute_family']['status'] = (bool) $product['attribute_family']['status'];
                    }

                    $product = $this->sanitizeDocumentKeys($product);

                    $productsToUpdate['body'][] = [
                        'index' => [
                            '_index' => $productIndex,
                            '_id'    => $productId,
                        ],
                    ];

                    $productsToUpdate['body'][] = $product;

                    $payloadByProductId[$productId] = $product;
                }

                $progressBar?->advance();
            }

            if ($productsToUpdate !== []) {
                $response = $this->bulkInChunks($productsToUpdate);

                if (isset($response['errors']) && $response['errors']) {
                    foreach ($response['items'] as $result) {
                        if (isset($result['index']['error'])) {
                            $failedProductIds[] = $result['index']['_id'];

                            Log::channel('elasticsearch')->error('Error while indexing product id: '.$result['index']['_id'].' in '.$productIndex.' index: ', [
                                'error' => $result['index']['error'],
                            ]);

                            if (config('elasticsearch.debug_payload', false)) {
                                $failedProductId = (int) $result['index']['_id'];
                                $failedPayload = $payloadByProductId[$failedProductId] ?? null;

                                Log::channel('elasticsearch')->error('Failed product payload debug: ', [
                                    'product_id'      => $failedProductId,
                                    'empty_key_paths' => is_array($failedPayload)
                                        ? $this->findEmptyFieldPaths($failedPayload)
                                        : [],
                                    'payload' => $failedPayload,
                                ]);
                            }
                        }
                    }
                }
            }

        }

        return $failedProductIds;
    }

    /**
     * Drop and recreate the index so a --fresh run starts from empty. Removes
     * the need to reconcile stale documents afterwards, which is the expensive
     * half of a rebuild.
     */
    protected function recreateIndex(string $productIndex): void
    {
        try {
            ElasticSearch::indices()->delete(['index' => $productIndex]);
        } catch (\Exception $e) {
            if (! str_contains($e->getMessage(), 'index_not_found_exception')) {
                throw $e;
            }
        }

        $this->elasticConfiguration($productIndex);
    }

    /**
     * Fork one child per id range. Document normalization is CPU-bound PHP —
     * a single process saturates one core and leaves the rest of the box idle,
     * so a full rebuild of a large catalog is core-count limited, not
     * Elasticsearch limited.
     *
     * A range whose fork fails, and every range when pcntl is unavailable, is
     * indexed in the parent instead: slower, but never silently unindexed.
     */
    protected function indexInParallel(string $productIndex, int $workers, bool $fresh): void
    {
        $bounds = DB::table('products')->selectRaw('MIN(id) AS lo, MAX(id) AS hi')->first();

        if (! $bounds || $bounds->lo === null) {
            return;
        }

        $forkable = function_exists('pcntl_fork') && function_exists('pcntl_waitpid');

        if (! $forkable) {
            $this->warn('The pcntl extension is unavailable — indexing in a single process.');
        }

        $span = (int) ceil((($bounds->hi - $bounds->lo) + 1) / $workers);
        $children = [];
        $failed = 0;

        for ($w = 0; $w < $workers; $w++) {
            $lo = $bounds->lo + ($w * $span);
            $hi = min((int) $bounds->hi, $lo + $span - 1);

            if ($lo > $hi) {
                continue;
            }

            $pid = $forkable ? pcntl_fork() : -1;

            if ($pid === -1) {
                if ($forkable) {
                    $this->warn('Could not fork worker '.$w.' — indexing its range in this process.');
                }

                if ($this->indexRange($productIndex, $lo, $hi, $fresh) !== []) {
                    $failed++;
                }

                continue;
            }

            if ($pid === 0) {
                DB::purge();
                DB::reconnect();

                try {
                    $failedIds = $this->indexRange($productIndex, $lo, $hi, $fresh);

                    exit($failedIds === [] ? 0 : 1);
                } catch (\Throwable $e) {
                    fwrite(STDERR, 'indexer worker '.$w.': '.$e->getMessage().PHP_EOL);
                    exit(1);
                }
            }

            $children[] = $pid;
        }

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error($failed.' indexer worker(s) reported failures — see elasticsearch.log.');
        }
    }

    /**
     * Delete documents whose product no longer exists, walking the index with
     * point-in-time + search_after. A single unbounded search would materialize
     * the whole index in heap on both the Elasticsearch and PHP sides.
     *
     * Reconciliation runs after the documents are already indexed, so a cluster
     * that cannot serve it is reported and skipped rather than failing the run.
     */
    protected function pruneStaleDocuments(string $productIndex): void
    {
        $this->info('Checking for stale products to delete...');

        $searchAfter = null;
        $deleted = 0;
        $pit = null;

        try {
            $pit = ElasticSearch::openPointInTime(['index' => $productIndex, 'keep_alive' => '5m'])['id'];

            while (true) {
                $body = [
                    '_source'     => false,
                    'query'       => ['match_all' => new \stdClass],
                    'size'        => self::BATCH_SIZE,
                    'sort'        => [['_shard_doc' => 'asc']],
                    'pit'         => ['id' => $pit, 'keep_alive' => '5m'],
                ];

                if ($searchAfter !== null) {
                    $body['search_after'] = $searchAfter;
                }

                $hits = ElasticSearch::search(['body' => $body])['hits']['hits'];

                if ($hits === []) {
                    break;
                }

                $searchAfter = $hits[array_key_last($hits)]['sort'];

                $ids = array_map(fn ($hit): int => (int) $hit['_id'], $hits);

                $alive = [];

                foreach (array_chunk($ids, 1000) as $idChunk) {
                    $alive = array_merge($alive, DB::table('products')->whereIn('id', $idChunk)->pluck('id')->all());
                }

                $stale = array_diff($ids, $alive);

                if ($stale !== []) {
                    $payload = [];

                    foreach ($stale as $productId) {
                        $payload['body'][] = ['delete' => ['_index' => $productIndex, '_id' => $productId]];
                    }

                    ElasticSearch::bulk($payload);

                    $deleted += count($stale);
                }
            }
        } catch (\Throwable $e) {
            $this->warn('Stale product prune skipped: '.$e->getMessage());

            Log::channel('elasticsearch')->warning('Stale product prune skipped.', ['error' => $e->getMessage()]);

            return;
        } finally {
            if ($pit !== null) {
                try {
                    ElasticSearch::closePointInTime(['body' => ['id' => $pit]]);
                } catch (\Throwable) {
                }
            }
        }

        $this->info($deleted === 0 ? 'No stale products to delete.' : $deleted.' stale products deleted.');

        Log::channel('elasticsearch')->info('Stale product prune completed.', ['deleted' => $deleted]);
    }

    /**
     * Send a bulk payload as several requests, keeping each action line paired
     * with the document that follows it.
     *
     * @param  array<string, mixed>  $payload
     * @return array{errors: bool, items: array<int, mixed>}
     */
    protected function bulkInChunks(array $payload): array
    {
        $items = [];
        $errors = false;

        foreach (array_chunk($payload['body'] ?? [], self::BULK_DOCUMENTS * 2) as $chunk) {
            $response = ElasticSearch::bulk(['body' => $chunk]);

            $errors = $errors || ($response['errors'] ?? false);

            $items = array_merge($items, $response['items'] ?? []);
        }

        return [
            'errors' => $errors,
            'items'  => $items,
        ];
    }

    /**
     * Get product updated at values from Elasticsearch
     *
     * @return mixed[]
     */
    public function getProductUpdates($productIndex, $command = null, array $searchIds = []): array
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

    /**
     * Check if the index exists
     */
    public function hasIndex($productIndex): bool
    {
        return ElasticSearch::indices()->exists(['index' => $productIndex])->asBool();
    }

    /**
     * Create Elasticsearch index with settings and mappings
     */
    public function elasticConfiguration($productIndex): void
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

    /**
     * Get index mappings for product
     */
    private function getUnopimProductMapping(): array
    {
        $statusMapping = ['type' => 'boolean'];

        return [
            'properties' => [
                'attribute_family' => [
                    'properties' => [
                        'code' => [
                            'type'   => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type'         => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                        ],
                        'id'     => ['type' => 'long'],
                        'name'   => [
                            'type'   => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type'         => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                        ],
                        'status'       => $statusMapping,
                        'translations' => [
                            'properties' => [
                                'attribute_family_id' => ['type' => 'long'],
                                'id'                  => ['type' => 'long'],
                                'locale'              => [
                                    'type'   => 'text',
                                    'fields' => [
                                        'keyword' => [
                                            'type'         => 'keyword',
                                            'ignore_above' => 256,
                                        ],
                                    ],
                                ],
                                'name'                => [
                                    'type'   => 'text',
                                    'fields' => [
                                        'keyword' => [
                                            'type'         => 'keyword',
                                            'ignore_above' => 256,
                                        ],
                                    ],
                                ],
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
                'status'     => $statusMapping,
                'type'       => [
                    'type'   => 'text',
                    'fields' => [
                        'keyword' => [
                            'type'         => 'keyword',
                            'ignore_above' => 256,
                        ],
                    ],
                ],
                'updated_at' => ['type' => 'date'],
            ],
            'dynamic_templates' => $this->dynamicAttributeMappings(),
        ];
    }

    /**
     * Attribute type wise dynamic field mapping templates
     *
     * See 'packages/Webkul/ElasticSearch/tests/Feature/ProductIndexTest.php' for full dynamic mapping template as an array
     *
     * @return mixed[]
     */
    protected function dynamicAttributeMappings(): array
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

    /**
     * Get index settings for product
     */
    private function getUnopimProductSetting(): array
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

    /**
     * Recursively removes empty-string keys from a product document payload.
     */
    private function sanitizeDocumentKeys(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $resolvedKey = $key;

            if (is_string($key)) {
                $resolvedKey = trim($key);

                if ($resolvedKey === '') {
                    continue;
                }
            }

            if (is_array($value)) {
                $value = $this->sanitizeDocumentKeys($value);
            }

            $sanitized[$resolvedKey] = $value;
        }

        return $sanitized;
    }

    /**
     * Finds all payload paths that contain empty-string keys.
     *
     * @return array<int, string>
     */
    private function findEmptyFieldPaths(array $data, string $path = '$'): array
    {
        $paths = [];

        foreach ($data as $key => $value) {
            $keyString = is_string($key) ? $key : (string) $key;
            $currentPath = $path.'.'.($keyString === '' ? '<empty>' : $keyString);

            if (is_string($key) && trim($key) === '') {
                $paths[] = $currentPath;
            }

            if (is_array($value)) {
                $paths = array_merge($paths, $this->findEmptyFieldPaths($value, $currentPath));
            }
        }

        return $paths;
    }
}
