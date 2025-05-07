<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Elastic;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Cursor\AbstractElasticCursor;

class ProductCursor extends AbstractElasticCursor
{
    private array $searchAfter = [];

    public function __construct(
        protected $elasticQuery,
        protected $source,
        protected int $size = 10,
        protected array $options = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        if (false === next($this->items)) {
            $this->position += count($this->items);
            $this->items = $this->getNextItems();
            reset($this->items);
        }
    }

    /**
     * Get next items from the source
     *
     * @param array $esQuery
     * @return array
     */
    public function getNextItems()
    {
        $totalItems = [];
        $ids = $this->getNextIds($this->elasticQuery, $this->size);
        // $newItems = $this->getNextItemsFromIds($ids);
        $totalItems = \array_merge($totalItems, $ids);
        
        return $totalItems;
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
     * Get next SKUs from the source
     *
     * @param array $esQuery
     * @param int|null $size
     * @return array
     */
    protected function getNextIds(array $esQuery, ?int $size = null): array
    {
        $options = self::resolveOptions($this->options);
        $sort = ['_id' => 'desc'];
        $query['track_total_hits'] = true;
        $query['_source'] = false;
        $query['size'] = $size;

        $query['sort'] = $sort;
        $query['stored_fields'] = [];
        
        if (!empty($this->searchAfter)) {
            $query['search_after'] = $this->searchAfter;
        }

        if (! isset($query['query'])) {
            $query['query']['bool'] = new \stdClass;
        }

        $requestParam = [
            'index' => $options['index'],
            'body'  => $query,
        ];

        $ids = [];

        try {
            $response = Elasticsearch::search($requestParam);
            $hits = $response['hits']['hits'] ?? [];

            $ids = array_map(fn($hit) => ['id' => $hit['_id']], $hits);
            $this->retrievedCount = $response['hits']['total']['value'];
            $lastHit = end($hits);
            $lastResult = end($hits); // Changed from response['hits']['hits'] to hits
            if (false !== $lastHit) {
                $this->searchAfter = $lastResult['sort'];
            }
        } catch (\Exception $e) {
            \Log::error('Elasticsearch search error: ' . $e->getMessage());
        }


        return $ids;
    }

    protected function getNextItemsFromIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->source->with([
            'attribute_family',
            'parent',
            'super_attributes',
        ])->whereIn('id', $ids)->orderBy('id', 'desc')->get()->toArray(); 
    }

    /**
     * @return array
     */
    protected static function resolveOptions(array $options)
    {
        $indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');

        $options['sort'] = $options['sort'] ?? [];
        $options['filters'] = $options['filters'] ?? [];

        $options['index'] = strtolower($indexPrefix.'_products');

        return $options;
    }
}