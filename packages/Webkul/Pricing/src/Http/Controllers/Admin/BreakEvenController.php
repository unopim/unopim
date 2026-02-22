<?php

namespace Webkul\Pricing\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\Pricing\Repositories\ChannelCostRepository;
use Webkul\Pricing\Repositories\ProductCostRepository;
use Webkul\Pricing\Services\BreakEvenCalculator;
use Webkul\Product\Repositories\ProductRepository;

class BreakEvenController extends Controller
{
    public function __construct(
        protected ProductCostRepository $productCostRepository,
        protected ChannelCostRepository $channelCostRepository,
        protected BreakEvenCalculator $breakEvenCalculator,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Calculate and display break-even for a product across all channels.
     */
    public function show(int $productId)
    {
        if (! bouncer()->hasPermission('pricing.break_even.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $product = $this->productRepository->find($productId);

        if (! $product) {
            abort(404);
        }

        $productCosts = $this->productCostRepository->getActiveCostsForProduct($productId);
        $channelCosts = $this->channelCostRepository->all();

        $breakdowns = [];

        foreach ($channelCosts as $channelCost) {
            $breakdowns[] = $this->breakEvenCalculator->calculate($product, $channelCost);
        }

        return view('pricing::admin.break-even.show', compact('product', 'productCosts', 'breakdowns'));
    }

    /**
     * Break-even for a specific product and channel (JSON for AJAX).
     */
    public function forChannel(int $productId, int $channelId)
    {
        if (! bouncer()->hasPermission('pricing.break_even.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $product = $this->productRepository->find($productId);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $channelCost = $this->channelCostRepository->getActiveForChannel($channelId);

        if (! $channelCost) {
            return response()->json(['message' => 'No active channel cost configuration found.'], 404);
        }

        $breakdown = $this->breakEvenCalculator->calculate($product, $channelCost);

        return response()->json([
            'data' => $breakdown,
        ]);
    }
}
