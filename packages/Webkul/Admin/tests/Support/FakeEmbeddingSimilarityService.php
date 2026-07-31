<?php

namespace Webkul\Admin\Tests\Support;

use Webkul\AiAgent\Services\EmbeddingSimilarityService;

/**
 * Deterministic ranking stand-in so similarity tests do not depend on a
 * configured embeddings provider: every candidate scores 1.0 in pool order.
 */
class FakeEmbeddingSimilarityService extends EmbeddingSimilarityService
{
    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        $scores = array_map(
            fn (int $index) => ['index' => $index, 'score' => 1.0],
            array_keys($documents),
        );

        return is_null($limit) ? $scores : array_slice($scores, 0, max(1, $limit));
    }
}
