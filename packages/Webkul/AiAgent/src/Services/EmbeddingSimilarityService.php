<?php

namespace Webkul\AiAgent\Services;

use Laravel\Ai\Embeddings;

/**
 * Semantic similarity scoring using laravel/ai embeddings.
 */
class EmbeddingSimilarityService
{
    /**
     * Rank documents by similarity to a query text.
     *
     * @param  array<int, string>  $documents
     * @return array<int, array{index: int, score: float}>
     */
    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        if (trim($query) === '' || empty($documents)) {
            return [];
        }

        try {
            $response = Embeddings::for(array_merge([$query], $documents))->generate();

            $vectors = $response->embeddings;
            $queryVector = $vectors[0] ?? null;

            if (! is_array($queryVector) || empty($queryVector)) {
                return [];
            }

            $scores = [];

            foreach (array_slice($vectors, 1) as $index => $vector) {
                if (! is_array($vector) || empty($vector)) {
                    continue;
                }

                $scores[] = [
                    'index' => $index,
                    'score' => $this->cosine($queryVector, $vector),
                ];
            }

            usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

            if (! is_null($limit)) {
                $scores = array_slice($scores, 0, max(1, $limit));
            }

            return $scores;
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
