<?php

namespace Webkul\Pricing\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Pricing\DataGrids\CostDataGrid;
use Webkul\Pricing\Events\CostUpdated;
use Webkul\Pricing\Http\Requests\CostRequest;
use Webkul\Pricing\Repositories\ProductCostRepository;

class CostController extends Controller
{
    public function __construct(
        protected ProductCostRepository $productCostRepository,
    ) {}

    /**
     * Display a listing of product costs.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('pricing.costs.view')) {
            abort(403, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            return app(CostDataGrid::class)->toJson();
        }

        return view('pricing::admin.costs.index');
    }

    /**
     * Show the form for creating a new cost entry.
     */
    public function create()
    {
        if (! bouncer()->hasPermission('pricing.costs.create')) {
            abort(403, 'This action is unauthorized.');
        }

        return view('pricing::admin.costs.create');
    }

    /**
     * Store a newly created cost entry.
     */
    public function store(CostRequest $request)
    {
        if (! bouncer()->hasPermission('pricing.costs.create')) {
            abort(403, 'This action is unauthorized.');
        }

        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = auth()->guard('admin')->id();

            $productCost = $this->productCostRepository->create($data);

            event(new CostUpdated($productCost, 0));

            session()->flash('success', trans('pricing::app.costs.create-success'));

            return redirect()->route('admin.pricing.costs.index');
        });
    }

    /**
     * Show the form for editing a cost entry.
     */
    public function edit(int $id)
    {
        if (! bouncer()->hasPermission('pricing.costs.edit')) {
            abort(403, 'This action is unauthorized.');
        }

        $cost = $this->productCostRepository->find($id);

        if (! $cost) {
            abort(404);
        }

        return view('pricing::admin.costs.edit', compact('cost'));
    }

    /**
     * Update the specified cost entry.
     */
    public function update(CostRequest $request, int $id)
    {
        if (! bouncer()->hasPermission('pricing.costs.edit')) {
            abort(403, 'This action is unauthorized.');
        }

        $cost = $this->productCostRepository->find($id);

        if (! $cost) {
            abort(404);
        }

        // Optimistic locking check (F-003)
        if ($request->has('version') && (int) $request->input('version') !== $cost->version) {
            abort(409, 'This record has been modified by another user. Please reload and try again.');
        }

        return DB::transaction(function () use ($request, $cost, $id) {
            $previousAmount = (float) $cost->amount;
            $data = $request->validated();
            $data['version'] = $cost->version + 1;

            $cost = $this->productCostRepository->update($data, $id);

            event(new CostUpdated($cost, $previousAmount));

            session()->flash('success', trans('pricing::app.costs.update-success'));

            return redirect()->route('admin.pricing.costs.index');
        });
    }

    /**
     * Remove the specified cost entry.
     */
    public function destroy(int $id)
    {
        if (! bouncer()->hasPermission('pricing.costs.delete')) {
            abort(403, 'This action is unauthorized.');
        }

        $cost = $this->productCostRepository->find($id);

        if (! $cost) {
            abort(404);
        }

        $this->productCostRepository->delete($id);

        session()->flash('success', trans('pricing::app.costs.delete-success'));

        return redirect()->route('admin.pricing.costs.index');
    }

    /**
     * Return costs breakdown for a specific product (JSON for AJAX).
     */
    public function forProduct(int $productId)
    {
        if (! bouncer()->hasPermission('pricing.costs.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $costs = $this->productCostRepository->getActiveCostsForProduct($productId);

        $totalByType = $costs->groupBy('cost_type')->map(function ($group) {
            return [
                'cost_type'   => $group->first()->cost_type,
                'total'       => $group->sum('amount'),
                'currency'    => $group->first()->currency_code,
                'entry_count' => $group->count(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'product_id'    => $productId,
                'costs'         => $costs,
                'total_by_type' => $totalByType,
                'grand_total'   => $costs->sum('amount'),
                'currency_code' => $costs->first()?->currency_code ?? 'USD',
            ],
        ]);
    }
}
