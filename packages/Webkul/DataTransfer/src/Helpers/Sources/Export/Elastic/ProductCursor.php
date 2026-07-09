<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Elastic;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataTransfer\Helpers\Sources\Export\Filters\ProductExportFilter;
use Webkul\ElasticSearch\Cursor\AbstractElasticCursor;

class ProductCursor extends AbstractElasticCursor
{
    /**
     * Memoized bool query. The filter clauses (family/category/value-filtered ids) are identical
     * for every page of a single export run, so they are resolved once instead of re-running the
     * underlying DB queries on every search_after fetch.
     *
     * @var array|\stdClass|null
     */
    protected $cachedBoolQuery = null;

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

        if ($this->cachedBoolQuery === null) {
            $this->cachedBoolQuery = $this->buildBoolQuery($filters) ?: new \stdClass;
        }

        $query['query']['bool'] = $this->cachedBoolQuery;

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

    protected function buildBoolQuery(array $filters): array
    {
        $filter = app(ProductExportFilter::class);

        $clauses = [];

        $status = $filter->statusValue($filters);

        if ($status !== null) {
            $clauses[] = ['term' => ['status' => $status]];
        }

        $familyIds = $filter->attributeFamilyIds($filters);

        if (! empty($familyIds)) {
            $clauses[] = ['terms' => ['attribute_family_id' => $familyIds]];
        }

        $categoryCodes = $filter->categoryCodes($filters);

        if (! empty($categoryCodes)) {
            $clauses[] = ['terms' => ['values.categories' => $categoryCodes]];
        }

        $range = array_filter([
            'gte' => $filter->updatedAfter($filters),
            'lte' => $filter->updatedBefore($filters),
        ], fn ($bound) => ! empty($bound));

        if (! empty($range)) {
            $clauses[] = ['range' => ['updated_at' => $range]];
        }

        $valueFilteredIds = $filter->valueFilteredIds($filters);

        if ($valueFilteredIds !== null) {
            $clauses[] = ['terms' => ['id' => $valueFilteredIds]];
        }

        return $clauses ? ['filter' => $clauses] : [];
    }

    /**
     * Resolve the Elasticsearch index and apply defaults.
     */
    protected static function resolveOptions(array $options): array
    {
        $prefix = config('elasticsearch.prefix');
        $options['index'] = strtolower("{$prefix}_products");
        $options['sort'] ??= [];

        return $options;
    }
}
