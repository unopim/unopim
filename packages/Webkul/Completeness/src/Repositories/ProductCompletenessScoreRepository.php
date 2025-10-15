<?php

namespace Webkul\Completeness\Repositories;

use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Eloquent\Repository;

class ProductCompletenessScoreRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ProductCompletenessScore::class;
    }

    /**
     * Get average score by channel and locale id.
     */
    public function getAverageScore(int $channelId, ?int $localeId = null): ?float
    {
        $query = $this->where('channel_id', $channelId);

        if (isset($localeId)) {
            $query->where('locale_id', $localeId);
        }

        return $query->avg('score');
    }

    public function countProductsWithCompletenessCalculated(int $channelId): int
    {
        return $this->where('channel_id', $channelId)->distinct('product_id')->count();
    }
}
