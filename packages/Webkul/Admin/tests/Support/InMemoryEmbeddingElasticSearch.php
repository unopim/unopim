<?php

namespace Webkul\Admin\Tests\Support;

use Mockery;
use Webkul\Core\Facades\ElasticSearch;

/**
 * Routes the ElasticSearch facade to an in-memory embedding index that applies
 * bulk index/update actions and the kNN attribute family term filter, so the
 * vector store can be asserted at payload level without a live cluster.
 */
class InMemoryEmbeddingElasticSearch
{
    /** @var array<int, array<int, array<string, mixed>>> */
    public array $bulkBodies = [];

    /** @var array<int, array<string, mixed>> */
    public array $searchBodies = [];

    /**
     * @param  array<int, array<string, mixed>>  $documents  stored _source keyed by product id
     */
    public function __construct(public array $documents = []) {}

    /**
     * Bind the fake to the ElasticSearch facade.
     */
    public function install(): static
    {
        $indices = Mockery::mock();
        $indices->shouldReceive('exists')->andReturn(Mockery::mock(['asBool' => true]));
        $indices->shouldReceive('putMapping')->andReturn([]);

        ElasticSearch::shouldReceive('indices')->andReturn($indices);
        ElasticSearch::shouldReceive('bulk')->andReturnUsing($this->bulk(...));
        ElasticSearch::shouldReceive('search')->andReturnUsing($this->search(...));

        return $this;
    }

    /**
     * @param  array{body: array<int, array<string, mixed>>}  $payload
     * @return array<string, mixed>
     */
    protected function bulk(array $payload): array
    {
        $this->bulkBodies[] = $payload['body'];

        foreach (array_chunk($payload['body'], 2) as [$action, $source]) {
            if (isset($action['index'])) {
                $this->documents[(int) $action['index']['_id']] = $source;
            }

            if (isset($action['update'])) {
                $id = (int) $action['update']['_id'];

                $this->documents[$id] = array_merge($this->documents[$id] ?? [], $source['doc']);
            }
        }

        return ['errors' => false, 'items' => []];
    }

    /**
     * @param  array{body: array<string, mixed>}  $params
     * @return array<string, mixed>
     */
    protected function search(array $params): array
    {
        $this->searchBodies[] = $params['body'];

        if (isset($params['body']['query']['ids'])) {
            $hits = array_intersect_key($this->documents, array_flip($params['body']['query']['ids']['values']));
        } else {
            $familyId = $params['body']['knn']['filter']['term']['attribute_family_id'] ?? null;

            $hits = array_filter(
                $this->documents,
                fn (array $source): bool => $familyId === null || ($source['attribute_family_id'] ?? null) === $familyId,
            );
        }

        return ['hits' => ['hits' => array_map(
            fn (int $id, array $source): array => ['_id' => (string) $id, '_score' => 0.9, '_source' => $source],
            array_keys($hits),
            array_values($hits),
        )]];
    }
}
