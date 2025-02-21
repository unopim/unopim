<?php

namespace Webkul\Product\ElasticSearch\Cursor;

use Webkul\Core\Facades\ElasticSearch;
use Webkul\ElasticSearch\Contracts\CursorFactoryInterface;
use Webkul\ElasticSearch\ElasticsearchResult;
use Webkul\ElasticSearch\ResultCursor;

class ResultCursorFactory implements CursorFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public static function createCursor($esQuery, array $options = [])
    {
        $options = self::resolveOptions($options);
        $sort = ['id' => 'asc'];
        $esQuery['size'] = $options['per_page'];
        $esQuery['from'] = ($options['page'] * $options['per_page']) - $options['per_page'];

        $esQuery['sort'] = isset($esQuery['sort']) ? array_merge($esQuery['sort'], $sort) : $sort;
        $esQuery['stored_fields'] = [];
        if (! isset($esQuery['query'])) {
            $esQuery['query']['bool'] = new \stdClass;
        }

        $requestParam = [
            'index' => $options['index'],
            'body'  => $esQuery,
        ];

        try {
            $results = Elasticsearch::search($requestParam);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'attribute_family_id')) {
                $results = Elasticsearch::search($requestParam);
            }
        }

        $totalResults = Elasticsearch::count([
            'index' => $options['index'],
            'body'  => [
                'query' => $esQuery['query'],
            ],
        ]);

        $totalCount = $totalResults['count'];

        $ids = collect($results['hits']['hits'])->pluck('_id')->toArray();

        return new ResultCursor($ids, $totalCount, new ElasticsearchResult($results['hits']['hits'] ?? []));
    }

    /**
     * @return array
     */
    protected static function resolveOptions(array $options)
    {
        $indexPrefix = env('ELASTICSEARCH_INDEX_PREFIX') ? env('ELASTICSEARCH_INDEX_PREFIX') : env('APP_NAME');

        $options['page'] = $options['page'] ?? 1;
        $options['per_page'] = $options['per_page'] ?? 10;
        $options['sort'] = $options['sort'] ?? [];
        $options['filters'] = $options['filters'] ?? [];

        $options['index'] = strtolower($indexPrefix.'_products');

        return $options;
    }
}
