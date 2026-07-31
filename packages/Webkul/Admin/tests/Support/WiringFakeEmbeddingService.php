<?php

namespace Webkul\Admin\Tests\Support;

use Webkul\AiAgent\Services\EmbeddingSimilarityService;

/**
 * Deterministic embedding stand-in: rankProducts() returns preset kNN hits,
 * rank() scores documents by needle match so pruning order is predictable.
 */
class WiringFakeEmbeddingService extends EmbeddingSimilarityService
{
    /** @var array<int, array{product_id: int, score: float}> */
    public array $knnHits = [];

    public string $needle = '';

    public function rankProducts(string $query, ?int $limit = null, ?int $attributeFamilyId = null): array
    {
        return array_slice($this->knnHits, 0, $limit ?? 10);
    }

    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        $scores = [];

        foreach ($documents as $index => $document) {
            $scores[] = [
                'index' => $index,
                'score' => str_contains($document, $this->needle) ? 1.0 : 0.1,
            ];
        }

        usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scores, 0, $limit ?? count($scores));
    }
}
