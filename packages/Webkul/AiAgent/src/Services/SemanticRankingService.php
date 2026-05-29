<?php

declare(strict_types=1);

namespace Webkul\AiAgent\Services;

use Laravel\Ai\Reranking;
use Laravel\Ai\Responses\Data\RankedDocument;

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
        if (trim($query) === '' || $documents === []) {
            return [];
        }

        try {
            $response = Reranking::of($documents)
                ->limit($limit)
                ->rerank($query);

            return array_map(
                fn (RankedDocument $result) => [
                    'index' => $result->index,
                    'score' => $result->score,
                ],
                $response->results,
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
