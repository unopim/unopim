<?php

namespace Webkul\Product\Factories\ElasticSearch\Cursor;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Contracts\CursorFactory as CursorFactoryContract;
use Webkul\ElasticSearch\Contracts\ResultCursor as ResultCursorContract;
use Webkul\ElasticSearch\ResultIterator;
use Webkul\ElasticSearch\SearchResponse;

class ResultCursorFactory implements CursorFactoryContract
{
    /**
     * {@inheritdoc}
     */
    public static function createCursor($query, array $options = []): ResultCursorContract
    {
        $options = self::resolveOptions($options);
        $sort = ['id' => 'asc'];
        $query['track_total_hits'] = true;
        $query['size'] = $options['per_page'];
        $query['from'] = ($options['page'] * $options['per_page']) - $options['per_page'];
        $query['sort'] = isset($query['sort']) ? $query['sort'] : $sort;
        $query['stored_fields'] = [];
        if (! isset($query['query'])) {
            $query['query']['bool'] = new \stdClass;
        }

        $requestParam = [
            'index' => $options['index'],
            'body'  => $query,
        ];

        try {
            $results = Elasticsearch::search($requestParam);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'attribute_family_id')) {
                try {
                    $results = Elasticsearch::search($requestParam);

                } catch (\Exception $retryException) {
                    throw $retryException;
                }
            } else {
                throw $e;
            }
        }
        $totalCount = $results['hits']['total']['value'] ?? 0;

        $ids = collect($results['hits']['hits'])->pluck('_id')->toArray();

        return new ResultIterator($ids, $totalCount, new SearchResponse($results['hits']['hits'] ?? []));
    }

    /**
     * @return array
     */
    protected static function resolveOptions(array $options)
    {
        $indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');

        $options['page'] = $options['pagination']['page'] ?? 1;
        $options['per_page'] = $options['pagination']['per_page'] ?? 10;
        $options['sort'] = $options['sort'] ?? [];
        $options['filters'] = $options['filters'] ?? [];

        $options['index'] = strtolower($indexPrefix.'_products');

        return $options;
    }
}
