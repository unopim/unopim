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

    /**
     * Average score of every channel, keyed by channel id.
     *
     * The dashboard needs one figure per channel and per channel-locale pair;
     * asking row by row costs a query per pair, which on a catalogue with a few
     * dozen channels and their locales runs to four figures. These three grouped
     * queries answer the whole panel instead.
     *
     * @return array<int, float>
     */
    public function getAverageScoresByChannel(): array
    {
        return $this->getModel()
            ->newQuery()
            ->selectRaw('channel_id, AVG(score) AS average')
            ->groupBy('channel_id')
            ->pluck('average', 'channel_id')
            ->map(fn ($average): float => (float) $average)
            ->all();
    }

    /**
     * Average score of every channel-locale pair, keyed by channel then locale id.
     *
     * @return array<int, array<int, float>>
     */
    public function getAverageScoresByChannelAndLocale(): array
    {
        $scores = [];

        $this->getModel()
            ->newQuery()
            ->selectRaw('channel_id, locale_id, AVG(score) AS average')
            ->groupBy('channel_id', 'locale_id')
            ->get()
            ->each(function ($row) use (&$scores): void {
                $scores[(int) $row->channel_id][(int) $row->locale_id] = (float) $row->average;
            });

        return $scores;
    }

    /**
     * Number of distinct products scored in each channel, keyed by channel id.
     *
     * @return array<int, int>
     */
    public function countProductsWithCompletenessByChannel(): array
    {
        return $this->getModel()
            ->newQuery()
            ->selectRaw('channel_id, COUNT(DISTINCT product_id) AS total')
            ->groupBy('channel_id')
            ->pluck('total', 'channel_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
