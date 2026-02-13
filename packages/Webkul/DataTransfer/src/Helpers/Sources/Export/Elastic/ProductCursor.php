<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Elastic;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Cursor\AbstractElasticCursor;

class ProductCursor extends AbstractElasticCursor
{
    public function __construct(
        array $requestParams,
        mixed $source,
        int $batchSize = 100,
        protected array $options = []
    ) {
        $this->requestParams = $requestParams;
        $this->source = $source;
        $this->batchSize = $batchSize;
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
            'sort'             => ['id' => 'desc'],
            'stored_fields'    => [],
        ];

        if (! empty($this->searchAfter)) {
            $query['search_after'] = $this->searchAfter;
        }

        $boolQuery = [];

        if (! empty($filters['status'])) {
            $value = $filters['status'] == 'enable' ? 1 : 0;
            $boolQuery['filter'][] = [
                'terms' => ['status' => [$value]],
            ];
        }

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
        $prefix = config('elasticsearch.prefix') ?: config('app.name');

        $tenantSuffix = '';
        $tenantId = core()->getCurrentTenantId();

        if ($tenantId) {
            try {
                $uuid = \Illuminate\Support\Facades\DB::table('tenants')
                    ->where('id', $tenantId)
                    ->value('es_index_uuid');

                $tenantSuffix = $uuid ? "_tenant_{$uuid}" : "_tenant_{$tenantId}";
            } catch (\Throwable) {
                $tenantSuffix = "_tenant_{$tenantId}";
            }
        }

        $options['index'] = strtolower($prefix.$tenantSuffix.'_products');
        $options['sort'] ??= [];

        return $options;
    }
}
