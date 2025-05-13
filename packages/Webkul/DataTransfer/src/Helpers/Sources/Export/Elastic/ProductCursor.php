<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Elastic;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Cursor\AbstractElasticCursor;

class ProductCursor extends AbstractElasticCursor
{
    private array $searchAfter = [];

    public function __construct(
        array $requestParams,
        $source,
        int $batchSize = 100,
        protected array $options = []
    ) {
        $this->requestParams = $requestParams;
        $this->source = $source;
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        if (next($this->items) === false) {
            $this->items = $this->getNextItems();
            reset($this->items);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->searchAfter = [];
        $this->items = $this->getNextItems();
        reset($this->items);
    }

    /**
     * Fetch the next batch of items.
     */
    protected function getNextItems(): array
    {
        return $this->fetchNextBatch($this->requestParams, $this->batchSize);
    }

    /**
     * Fetch a batch of product IDs from Elasticsearch.
     */
    protected function fetchNextBatch(array $requestParams = [], ?int $size = null): array
    {
        $options = self::resolveOptions($this->options);
        $filters = $requestParams['filters'] ?? [];
        $query = [
            'track_total_hits' => true,
            '_source'          => false,
            'size'             => $size ?? $this->batchSize,
            'sort'             => ['_id' => 'desc'],
            'stored_fields'    => [],
        ];

        if (! empty($this->searchAfter)) {
            $query['search_after'] = $this->searchAfter;
        }

        // Build the bool query
        $boolQuery = [];

        // @TODO: Need to future
        if (! empty($filters['status'])) {
            $boolQuery['filter'][] = [
                'terms' => ['status' => [$filters['status']]],
            ];
        }

        // Set the final query
        $query['query']['bool'] = $boolQuery ?: new \stdClass;

        $request = [
            'index' => $options['index'],
            'body'  => $query,
        ];

        try {

            $response = ElasticSearch::search($request);
            $hits = $response['hits']['hits'] ?? [];
            $this->retrievedCount = $response['hits']['total']['value'] ?? 0;

            if (! empty($hits)) {
                $this->searchAfter = end($hits)['sort'];

                return array_map(fn ($hit) => ['id' => $hit['_id']], $hits);
            }
        } catch (\Throwable $e) {
            \Log::error('Elasticsearch search error: '.$e->getMessage());
            throw $e;
        }

        return [];
    }

    /**
     * Resolve the Elasticsearch index and apply defaults.
     */
    protected static function resolveOptions(array $options): array
    {
        $prefix = env('ELASTICSEARCH_INDEX_PREFIX') ?: env('APP_NAME');
        $options['index'] = strtolower("{$prefix}_products");
        $options['sort'] ??= [];

        return $options;
    }
}
