<?php

namespace Webkul\Pricing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Pricing\Repositories\ChannelCostRepository;
use Webkul\Pricing\Services\RecommendedPriceEngine;
use Webkul\Product\Repositories\ProductRepository;

class RecommendationApiController extends Controller
{
    public function __construct(
        protected RecommendedPriceEngine $recommendedPriceEngine,
        protected ProductRepository $productRepository,
        protected ChannelCostRepository $channelCostRepository,
    ) {}

    /**
     * POST generate price recommendations for a product.
     */
    public function recommend(Request $request, string $productCode): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.recommendations.view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $product = $this->productRepository->findOneByField('sku', $productCode);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $channelId = $request->get('channel_id');

        if ($channelId) {
            $channelCost = $this->channelCostRepository->getActiveForChannel((int) $channelId);

            if (! $channelCost) {
                return response()->json(['message' => 'No active channel cost configuration found for this channel.'], 404);
            }

            $tiers = $this->recommendedPriceEngine->recommend($product, $channelCost);

            return response()->json([
                'data' => [
                    'product_sku' => $product->sku,
                    'product_id'  => $product->id,
                    'channel_id'  => (int) $channelId,
                    'tiers'       => $tiers,
                ],
            ]);
        }

        // Generate recommendations for all channels
        $channelCosts = $this->channelCostRepository->all();

        $recommendations = [];

        foreach ($channelCosts as $channelCost) {
            $recommendations[] = [
                'channel_id'   => $channelCost->channel_id,
                'channel_name' => $channelCost->channel?->name ?? "Channel #{$channelCost->channel_id}",
                'tiers'        => $this->recommendedPriceEngine->recommend($product, $channelCost),
            ];
        }

        return response()->json([
            'data' => [
                'product_sku'     => $product->sku,
                'product_id'      => $product->id,
                'recommendations' => $recommendations,
            ],
        ]);
    }
}
