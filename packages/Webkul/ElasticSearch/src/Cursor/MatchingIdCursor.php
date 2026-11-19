<?php

namespace Webkul\ElasticSearch\Cursor;

use Illuminate\Support\LazyCollection;
use Webkul\Core\Facades\ElasticSearch;

class MatchingIdCursor
{
    /**
     * Stream the id of every document matching the query, paging with `search_after` on `id`
     * so the walk is not bounded by the index's `max_result_window`.
     *
     * The first page is fetched eagerly so an unreachable cluster fails before iteration starts.
     *
     * @param  array<string, mixed>  $query
     * @return LazyCollection<int, int>
     */
    public static function lazy(string $index, array $query, int $batchSize): LazyCollection
    {
        $body = [
            'query'            => $query ?: ['bool' => new \stdClass],
            'size'             => $batchSize,
            'sort'             => ['id' => 'asc'],
            '_source'          => false,
            'stored_fields'    => [],
            'track_total_hits' => false,
        ];

        $hits = self::search($index, $body);

        return LazyCollection::make(function () use ($index, $body, $hits, $batchSize) {
            while (true) {
                foreach ($hits as $hit) {
                    yield (int) $hit['_id'];
                }

                if (count($hits) < $batchSize) {
                    return;
                }

                $body['search_after'] = end($hits)['sort'];

                $hits = self::search($index, $body);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<int, array<string, mixed>>
     */
    protected static function search(string $index, array $body): array
    {
        return ElasticSearch::search(['index' => $index, 'body' => $body])['hits']['hits'] ?? [];
    }
}
