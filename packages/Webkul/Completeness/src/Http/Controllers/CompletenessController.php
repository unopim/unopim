<?php

namespace Webkul\Completeness\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Completeness\Repositories\ProductCompletenessScoreRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Repositories\ProductRepository;

class CompletenessController extends Controller
{
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected ProductCompletenessScoreRepository $scoreRepository,
        protected ProductRepository $productRepository
    ) {}

    public function getCompletenessData(): JsonResponse
    {
        $channels = $this->channelRepository->with('locales')->all();
        $data = [];

        $totalProductCount = $this->productRepository->count();

        $scoredCounts = $this->scoreRepository->countProductsWithCompletenessByChannel();
        $channelAverages = $this->scoreRepository->getAverageScoresByChannel();
        $localeAverages = $this->scoreRepository->getAverageScoresByChannelAndLocale();

        foreach ($channels as $channel) {
            $channelCode = $channel->code;
            $channelId = $channel->id;
            $channelName = $channel->name;

            if (empty($channelName)) {
                $channelName = "[$channelCode]";
            }
            $data[$channelCode] = [
                'product_count'        => number_format($scoredCounts[$channelId] ?? 0),
                'total_products_count' => number_format($totalProductCount),
            ];

            foreach ($channel->locales as $locale) {
                $localeName = $locale->name;

                $data[$channelCode]['locales'][$localeName] = round($localeAverages[$channelId][$locale->id] ?? 0);
            }

            $data[$channelCode]['average'] = round($channelAverages[$channelId] ?? 0);
            $data[$channelCode]['name'] = $channelName;
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
