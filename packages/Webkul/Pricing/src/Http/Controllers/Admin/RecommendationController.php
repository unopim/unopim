<?php

namespace Webkul\Pricing\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Pricing\Events\RecommendationApplied;
use Webkul\Pricing\Repositories\ChannelCostRepository;
use Webkul\Pricing\Services\RecommendedPriceEngine;
use Webkul\Product\Repositories\ProductRepository;

class RecommendationController extends Controller
{
    public function __construct(
        protected RecommendedPriceEngine $recommendedPriceEngine,
        protected ProductRepository $productRepository,
        protected ChannelCostRepository $channelCostRepository,
    ) {}

    /**
     * Display price recommendations for a product per channel.
     */
    public function show(int $productId)
    {
        if (! bouncer()->hasPermission('pricing.recommendations.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $product = $this->productRepository->find($productId);

        if (! $product) {
            abort(404);
        }

        $channelCosts = $this->channelCostRepository->all();

        $recommendations = [];

        foreach ($channelCosts as $channelCost) {
            $recommendations[] = [
                'channel_id'   => $channelCost->channel_id,
                'channel_name' => $channelCost->channel?->name ?? "Channel #{$channelCost->channel_id}",
                'tiers'        => $this->recommendedPriceEngine->recommend($product, $channelCost),
            ];
        }

        return view('pricing::admin.recommendations.show', compact('product', 'recommendations'));
    }

    /**
     * Apply a recommendation tier to a channel for a product.
     */
    public function apply(Request $request, int $productId)
    {
        if (! bouncer()->hasPermission('pricing.recommendations.apply')) {
            abort(403, 'This action is unauthorized.');
        }

        $product = $this->productRepository->find($productId);

        if (! $product) {
            abort(404);
        }

        $request->validate([
            'channel_id' => ['required', 'exists:channels,id'],
            'tier'       => ['required', 'string', 'in:minimum,target,premium'],
            'price'      => ['required', 'numeric', 'min:0'],
        ]);

        $channelId = (int) $request->input('channel_id');
        $tier = $request->input('tier');
        $price = (float) $request->input('price');

        event(new RecommendationApplied(
            $productId,
            $channelId,
            $tier,
            $price
        ));

        session()->flash('success', trans('pricing::app.recommendations.apply-success', [
            'tier'  => $tier,
            'price' => number_format($price, 2),
        ]));

        return redirect()->route('admin.pricing.recommendations.show', $productId);
    }
}
