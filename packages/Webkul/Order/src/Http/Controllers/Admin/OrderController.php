<?php

namespace Webkul\Order\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Order\DataGrids\Admin\OrderDataGrid;
use Webkul\Order\Events\OrderStatusUpdated;
use Webkul\Order\Repositories\OrderRepository;

/**
 * Order Controller
 *
 * Handles admin order management operations including listing, viewing,
 * updating status, bulk operations, and export functionality.
 */
class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  OrderRepository  $orderRepository
     * @return void
     */
    public function __construct(protected OrderRepository $orderRepository)
    {
    }

    /**
     * Display a listing of orders.
     *
     * @return View|JsonResponse
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(OrderDataGrid::class)->process();
        }

        return view('order::admin.orders.index');
    }

    /**
     * Show the form for creating a new order.
     * Not applicable as orders are synced from channels.
     *
     * @return RedirectResponse
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('admin.orders.index')
            ->with('warning', trans('order::app.admin.orders.create-not-applicable'));
    }

    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return View
     */
    public function show(int $id): View
    {
        if (! bouncer()->allows('orders.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $order = $this->orderRepository->findOrFail($id);

        // Calculate profitability
        $profitability = $this->calculateOrderProfitability($order);

        return view('order::admin.orders.show', [
            'order' => $order,
            'profitability' => $profitability,
        ]);
    }

    /**
     * Show the form for editing the specified order.
     * Limited editing allowed (status, notes only).
     *
     * @param  int  $id
     * @return View
     */
    public function edit(int $id): View
    {
        if (! bouncer()->allows('orders.edit')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $order = $this->orderRepository->findOrFail($id);

        $allowedStatuses = config('order.statuses', [
            'pending', 'processing', 'completed', 'cancelled', 'refunded',
        ]);

        return view('order::admin.orders.edit', [
            'order' => $order,
            'allowedStatuses' => $allowedStatuses,
        ]);
    }

    /**
     * Update the specified order in storage.
     * Only status and notes can be updated.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        if (! bouncer()->allows('orders.edit')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $request->validate([
            'status' => 'sometimes|string|in:pending,processing,completed,cancelled,refunded',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $order = $this->orderRepository->findOrFail($id);
        $oldStatus = $order->status;

        $data = $request->only(['status', 'admin_notes']);

        $this->orderRepository->update($data, $id);

        // Dispatch event if status changed
        if (isset($data['status']) && $oldStatus !== $data['status']) {
            Event::dispatch(new OrderStatusUpdated($order->fresh(), $oldStatus));
        }

        return redirect()->route('admin.orders.show', $id)
            ->with('success', trans('order::app.admin.orders.update-success'));
    }

    /**
     * Remove the specified order from storage (soft delete).
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        if (! bouncer()->allows('orders.delete')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        try {
            $this->orderRepository->delete($id);

            return response()->json([
                'message' => trans('order::app.admin.orders.delete-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.admin.orders.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass update orders (bulk status update).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function massUpdate(Request $request): JsonResponse
    {
        if (! bouncer()->allows('orders.edit')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        $request->validate([
            'indices' => 'required|array',
            'indices.*' => 'integer',
            'status' => 'required|string|in:pending,processing,completed,cancelled,refunded',
        ]);

        $updated = 0;

        foreach ($request->indices as $orderId) {
            try {
                $order = $this->orderRepository->find($orderId);

                if ($order) {
                    $oldStatus = $order->status;

                    $this->orderRepository->update([
                        'status' => $request->status,
                    ], $orderId);

                    Event::dispatch(new OrderStatusUpdated($order->fresh(), $oldStatus));

                    $updated++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'message' => trans('order::app.admin.orders.mass-update-success', ['count' => $updated]),
        ]);
    }

    /**
     * Export orders to CSV/Excel.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export()
    {
        if (! bouncer()->allows('orders.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        return datagrid(OrderDataGrid::class)->export();
    }

    /**
     * Calculate order profitability.
     *
     * @param  \Webkul\Order\Models\Order  $order
     * @return array
     */
    protected function calculateOrderProfitability($order): array
    {
        $revenue = (float) $order->total_amount;
        $cost = 0;

        // Calculate total cost from order items
        foreach ($order->items as $item) {
            if ($item->product) {
                $productCost = (float) ($item->product->values['common']['cost'] ?? 0);
                $cost += $productCost * $item->quantity;
            }
        }

        $profit = $revenue - $cost;
        $marginPercentage = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'margin_percentage' => round($marginPercentage, 2),
        ];
    }
}
