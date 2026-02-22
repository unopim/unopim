<?php

namespace Webkul\Pricing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Pricing\Repositories\ChannelCostRepository;
use Webkul\Pricing\Services\BreakEvenCalculator;
use Webkul\Product\Repositories\ProductRepository;

class BreakEvenApiController extends Controller
{
    public function __construct(
        protected BreakEvenCalculator $breakEvenCalculator,
        protected ProductRepository $productRepository,
        protected ChannelCostRepository $channelCostRepository,
    ) {}

    /**
     * GET break-even data for a product, optionally filtered by channel.
     */
    public function show(Request $request, string $productCode): JsonResponse
    {
        if (! bouncer()->hasPermission('pricing.break_even.view')) {
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

            $breakdown = $this->breakEvenCalculator->calculate($product, $channelCost);

            return response()->json([
                'data' => [
                    'product_sku' => $product->sku,
                    'product_id'  => $product->id,
                    'channel_id'  => (int) $channelId,
                    'breakdown'   => $breakdown,
                ],
            ]);
        }

        // Return break-even for all channels
        $channelCosts = $this->channelCostRepository->all();

        $breakdowns = [];

        foreach ($channelCosts as $channelCost) {
            $breakdowns[] = [
                'channel_id'   => $channelCost->channel_id,
                'channel_name' => $channelCost->channel?->name ?? "Channel #{$channelCost->channel_id}",
                'breakdown'    => $this->breakEvenCalculator->calculate($product, $channelCost),
            ];
        }

        return response()->json([
            'data' => [
                'product_sku' => $product->sku,
                'product_id'  => $product->id,
                'channels'    => $breakdowns,
            ],
        ]);
    }
}
