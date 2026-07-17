<?php

namespace Webkul\AiAgent\Services\VectorStore;

use Illuminate\Support\Facades\Log;
use Webkul\Core\Facades\ElasticSearch;

/**
 * Manages the Elasticsearch dense_vector index that persists product embeddings.
 *
 * Index name and field names are built exclusively from class constants and the
 * elasticsearch config prefix — never from user input.
 */
class ProductEmbeddingIndex
{
    /**
     * Suffix appended to the configured Elasticsearch index prefix.
     */
    public const INDEX_SUFFIX = '_ai_product_embeddings';

    /**
     * Name of the dense_vector field inside the index.
     */
    public const VECTOR_FIELD = 'embedding';

    /**
     * Whether the vector store may talk to Elasticsearch at all.
     *
     * Requires both the AiAgent feature toggle and Elasticsearch itself to be
     * enabled, so no catalog data ever leaves the database while disabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('ai-agent.vector_store.enabled')
            && (bool) config('elasticsearch.enabled');
    }

    /**
     * Fully-qualified index name following the ElasticSearch package convention.
     */
    public function indexName(): string
    {
        return strtolower(config('elasticsearch.prefix').self::INDEX_SUFFIX);
    }

    /**
     * Configured embedding vector dimensions.
     */
    public function dimensions(): int
    {
        return max(1, (int) (config('ai-agent.vector_store.dimensions') ?: 1536));
    }

    /**
     * Index mappings for the embedding documents.
     *
     * @return array<string, mixed>
     */
    public function mappings(): array
    {
        return [
            'properties' => [
                'product_id'          => ['type' => 'long'],
                'sku'                 => ['type' => 'keyword'],
                'attribute_family_id' => ['type' => 'long'],
                'content_hash'        => ['type' => 'keyword'],
                'updated_at'          => ['type' => 'date'],

                self::VECTOR_FIELD => [
                    'type'       => 'dense_vector',
                    'dims'       => $this->dimensions(),
                    'index'      => true,
                    'similarity' => 'cosine',
                ],
            ],
        ];
    }

    /**
     * Create the index with its mapping when it does not exist yet.
     */
    public function ensureIndex(): void
    {
        if (ElasticSearch::indices()->exists(['index' => $this->indexName()])->asBool()) {
            // Additive and idempotent — backfills fields (e.g.
            // attribute_family_id) onto indexes created by older versions so
            // kNN filters match instead of silently returning zero hits.
            ElasticSearch::indices()->putMapping([
                'index' => $this->indexName(),
                'body'  => $this->mappings(),
            ]);

            return;
        }

        ElasticSearch::indices()->create([
            'index' => $this->indexName(),
            'body'  => [
                'mappings' => $this->mappings(),
            ],
        ]);
    }

    /**
     * Bulk upsert embedding documents keyed by product id.
     *
     * @param  array<int, array{product_id: int, sku: ?string, content_hash: string, embedding: array<int, float>}>  $documents
     * @return array<int, int> product ids that failed to index
     */
    public function bulkUpsert(array $documents): array
    {
        if (empty($documents)) {
            return [];
        }

        $payload = ['body' => []];

        foreach ($documents as $document) {
            $payload['body'][] = [
                'index' => [
                    '_index' => $this->indexName(),
                    '_id'    => $document['product_id'],
                ],
            ];

            $payload['body'][] = [
                'product_id'          => (int) $document['product_id'],
                'sku'                 => $document['sku'],
                'attribute_family_id' => isset($document['attribute_family_id']) ? (int) $document['attribute_family_id'] : null,
                'content_hash'        => $document['content_hash'],
                'updated_at'          => now()->toIso8601String(),
                self::VECTOR_FIELD    => $document['embedding'],
            ];
        }

        $response = ElasticSearch::bulk($payload);

        $failedIds = [];

        if (! empty($response['errors'])) {
            foreach ($response['items'] ?? [] as $item) {
                if (isset($item['index']['error'])) {
                    $failedIds[] = (int) $item['index']['_id'];
                }
            }

            if ($failedIds) {
                Log::channel('elasticsearch')->error('Failed to index product embeddings in '.$this->indexName().' index.', [
                    'product_ids' => $failedIds,
                ]);
            }
        }

        return $failedIds;
    }

    /**
     * Remove a product's embedding document from the index.
     */
    public function deleteByProductId(int $productId): void
    {
        try {
            ElasticSearch::delete([
                'index' => $this->indexName(),
                'id'    => $productId,
            ]);
        } catch (\Exception $e) {
            if (! str_contains($e->getMessage(), 'index_not_found_exception') && ! str_contains($e->getMessage(), '404')) {
                Log::channel('elasticsearch')->error('Exception while deleting product embedding id: '.$productId.' from '.$this->indexName().' index.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fetch stored content hashes for the given product ids, so unchanged
     * products can be skipped without re-embedding.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, string> content hash keyed by product id
     */
    public function existingContentHashes(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            $response = ElasticSearch::search([
                'index' => $this->indexName(),
                'body'  => [
                    '_source' => ['content_hash'],
                    'query'   => [
                        'ids' => ['values' => array_values(array_map('intval', $productIds))],
                    ],
                    'size' => count($productIds),
                ],
            ]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'index_not_found_exception')) {
                return [];
            }

            throw $e;
        }

        $hashes = [];

        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $hashes[(int) $hit['_id']] = (string) ($hit['_source']['content_hash'] ?? '');
        }

        return $hashes;
    }

    /**
     * Run a bounded kNN search against the stored product embeddings,
     * optionally restricted to one attribute family.
     *
     * @param  array<int, float|int>  $queryVector
     * @return array<int, array{product_id: int, score: float}> sorted by score descending
     */
    public function searchSimilar(array $queryVector, int $limit, ?int $attributeFamilyId = null): array
    {
        $k = min(max(1, $limit), max(1, (int) config('ai-agent.vector_store.knn.max_results', 50)));

        $knn = [
            'field'          => self::VECTOR_FIELD,
            'query_vector'   => array_map('floatval', $queryVector),
            'k'              => $k,
            'num_candidates' => max($k, (int) config('ai-agent.vector_store.knn.num_candidates', 100)),
        ];

        if ($attributeFamilyId !== null) {
            $knn['filter'] = [
                'term' => ['attribute_family_id' => $attributeFamilyId],
            ];
        }

        $response = ElasticSearch::search([
            'index' => $this->indexName(),
            'body'  => [
                '_source' => ['product_id'],
                'knn'     => $knn,
                'size'    => $k,
            ],
        ]);

        $results = [];

        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $productId = (int) ($hit['_source']['product_id'] ?? $hit['_id']);

            $results[] = [
                'product_id' => $productId,
                'score'      => (float) ($hit['_score'] ?? 0.0),
            ];
        }

        return $results;
    }
}
