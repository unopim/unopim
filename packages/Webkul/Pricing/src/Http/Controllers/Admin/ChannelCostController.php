<?php

namespace Webkul\Pricing\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\Pricing\Events\CostUpdated;
use Webkul\Pricing\Http\Requests\ChannelCostRequest;
use Webkul\Pricing\Repositories\ChannelCostRepository;

class ChannelCostController extends Controller
{
    public function __construct(
        protected ChannelCostRepository $channelCostRepository,
    ) {}

    /**
     * Display a listing of channel costs.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('pricing.channel_costs.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $channelCosts = $this->channelCostRepository->all();

        return view('pricing::admin.channel-costs.index', compact('channelCosts'));
    }

    /**
     * Store a newly created channel cost configuration.
     */
    public function store(ChannelCostRequest $request)
    {
        if (! bouncer()->hasPermission('pricing.channel_costs.create')) {
            abort(403, 'This action is unauthorized.');
        }

        $data = $request->validated();

        // Check if active configuration already exists for this channel
        $existing = $this->channelCostRepository->getActiveForChannel($data['channel_id']);

        if ($existing) {
            // Close the effective period of the existing configuration
            $this->channelCostRepository->update(
                ['effective_to' => $data['effective_from']],
                $existing->id
            );
        }

        $channelCost = $this->channelCostRepository->create($data);

        session()->flash('success', trans('pricing::app.channel-costs.create-success'));

        return redirect()->route('admin.pricing.channel-costs.index');
    }

    /**
     * Update the specified channel cost configuration.
     */
    public function update(ChannelCostRequest $request, int $id)
    {
        if (! bouncer()->hasPermission('pricing.channel_costs.edit')) {
            abort(403, 'This action is unauthorized.');
        }

        $channelCost = $this->channelCostRepository->find($id);

        if (! $channelCost) {
            abort(404);
        }

        $data = $request->validated();

        $channelCost = $this->channelCostRepository->update($data, $id);

        session()->flash('success', trans('pricing::app.channel-costs.update-success'));

        return redirect()->route('admin.pricing.channel-costs.index');
    }
}
