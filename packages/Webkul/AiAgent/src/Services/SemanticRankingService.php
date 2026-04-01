<?php

namespace Webkul\AiAgent\Services;

use Laravel\Ai\Reranking;

/**
 * Semantic ranking helper powered by laravel/ai.
 *
 * Falls back gracefully when reranking provider/config is unavailable.
 */
class SemanticRankingService
{
    /**
     * Rank the given candidate documents by relevance to the query.
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
            $response = Reranking::of($documents)
                ->limit($limit)
                ->rerank($query);

            return array_map(
                fn ($result) => [
                    'index' => $result->index,
                    'score' => (float) $result->score,
                ],
                $response->results,
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
