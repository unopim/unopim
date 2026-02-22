<?php

namespace Webkul\Pricing\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\Pricing\DataGrids\StrategyDataGrid;
use Webkul\Pricing\Http\Requests\StrategyRequest;
use Webkul\Pricing\Repositories\PricingStrategyRepository;

class StrategyController extends Controller
{
    public function __construct(
        protected PricingStrategyRepository $strategyRepository,
    ) {}

    /**
     * Display a listing of pricing strategies.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('pricing.strategies.view')) {
            abort(403, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            return app(StrategyDataGrid::class)->toJson();
        }

        return view('pricing::admin.strategies.index');
    }

    /**
     * Show the form for creating a new pricing strategy.
     */
    public function create()
    {
        if (! bouncer()->hasPermission('pricing.strategies.create')) {
            abort(403, 'This action is unauthorized.');
        }

        return view('pricing::admin.strategies.create');
    }

    /**
     * Store a newly created pricing strategy.
     */
    public function store(StrategyRequest $request)
    {
        if (! bouncer()->hasPermission('pricing.strategies.create')) {
            abort(403, 'This action is unauthorized.');
        }

        $data = $request->validated();

        $this->strategyRepository->create($data);

        session()->flash('success', trans('pricing::app.strategies.create-success'));

        return redirect()->route('admin.pricing.strategies.index');
    }

    /**
     * Show the form for editing a pricing strategy.
     */
    public function edit(int $id)
    {
        if (! bouncer()->hasPermission('pricing.strategies.edit')) {
            abort(403, 'This action is unauthorized.');
        }

        $strategy = $this->strategyRepository->find($id);

        if (! $strategy) {
            abort(404);
        }

        return view('pricing::admin.strategies.edit', compact('strategy'));
    }

    /**
     * Update the specified pricing strategy.
     */
    public function update(StrategyRequest $request, int $id)
    {
        if (! bouncer()->hasPermission('pricing.strategies.edit')) {
            abort(403, 'This action is unauthorized.');
        }

        $strategy = $this->strategyRepository->find($id);

        if (! $strategy) {
            abort(404);
        }

        $data = $request->validated();

        $this->strategyRepository->update($data, $id);

        session()->flash('success', trans('pricing::app.strategies.update-success'));

        return redirect()->route('admin.pricing.strategies.index');
    }

    /**
     * Remove the specified pricing strategy.
     */
    public function destroy(int $id)
    {
        if (! bouncer()->hasPermission('pricing.strategies.delete')) {
            abort(403, 'This action is unauthorized.');
        }

        $strategy = $this->strategyRepository->find($id);

        if (! $strategy) {
            abort(404);
        }

        $this->strategyRepository->delete($id);

        session()->flash('success', trans('pricing::app.strategies.delete-success'));

        return redirect()->route('admin.pricing.strategies.index');
    }
}
