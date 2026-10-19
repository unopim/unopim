<?php

namespace Webkul\Admin\Tests\Support;

use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Deterministic ranking stand-in so similarity tests do not depend on a
 * configured embeddings provider: every candidate scores 1.0 in pool order.
 */
class FakeEmbeddingSimilarityService extends EmbeddingSimilarityService
{
    public function resolvePlatform(?MagicAIPlatform $preferred = null, ?int $dimensions = null): ?MagicAIPlatform
    {
        return $preferred;
    }

    public function rankOrFail(string $query, array $documents, ?int $limit = null, ?MagicAIPlatform $platform = null): array
    {
        $scores = array_map(
            fn (int $index) => ['index' => $index, 'score' => 1.0],
            array_keys($documents),
        );

        return is_null($limit) ? $scores : array_slice($scores, 0, max(1, $limit));
    }
}
