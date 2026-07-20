<?php

namespace Webkul\AiAgent\Services;

use Laravel\Ai\Embeddings;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;

/**
 * Semantic similarity scoring using laravel/ai embeddings.
 */
class EmbeddingSimilarityService
{
    public function __construct(protected ?ProductEmbeddingIndex $productEmbeddingIndex = null) {}

    /**
     * Rank documents by similarity to a query text.
     *
     * @param  array<int, string>  $documents
     * @return array<int, array{index: int, score: float}>
     */
    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        if (trim($query) === '' || $documents === []) {
            return [];
        }

        try {
            $response = Embeddings::for(array_merge([$query], $documents))
                ->cache()
                ->generate();

            $vectors = $response->embeddings;
            $queryVector = $vectors[0] ?? null;

            if (! is_array($queryVector) || $queryVector === []) {
                return [];
            }

            $scores = [];

            foreach (array_slice($vectors, 1) as $index => $vector) {
                if (! is_array($vector)) {
                    continue;
                }
                if ($vector === []) {
                    continue;
                }
                $scores[] = [
                    'index' => $index,
                    'score' => $this->cosine($queryVector, $vector),
                ];
            }

            usort($scores, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

            if (! is_null($limit)) {
                return array_slice($scores, 0, max(1, $limit));
            }

            return $scores;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Rank products by similarity to a query using the persistent vector store.
     *
     * Runs an Elasticsearch kNN search against pre-indexed product embeddings.
     * Returns [] when the vector store is disabled or on any failure, so
     * callers can fall back to their existing ranking path.
     *
     * @return array<int, array{product_id: int, score: float}> sorted by score descending
     */
    public function rankProducts(string $query, ?int $limit = null, ?int $attributeFamilyId = null): array
    {
        $index = $this->productEmbeddingIndex ?? resolve(ProductEmbeddingIndex::class);

        if (trim($query) === '' || ! $index->isEnabled()) {
            return [];
        }

        try {
            $response = Embeddings::for([$query])
                ->cache()
                ->generate();

            $queryVector = $response->embeddings[0] ?? null;

            if (! is_array($queryVector) || $queryVector === []) {
                return [];
            }

            return $index->searchSimilar($queryVector, $limit ?? 10, $attributeFamilyId);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Compute cosine similarity between two vectors.
     *
     * @param  array<int, float|int>  $a
     * @param  array<int, float|int>  $b
     */
    protected function cosine(array $a, array $b): float
    {
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $av = (float) $a[$i];
            $bv = (float) $b[$i];
            $dot += $av * $bv;
            $normA += $av * $av;
            $normB += $bv * $bv;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
