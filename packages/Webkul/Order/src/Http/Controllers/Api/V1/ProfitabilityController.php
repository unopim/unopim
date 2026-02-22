<?php

namespace Webkul\Order\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\AdminApi\Http\Controllers\V1\Controller;
use Webkul\Order\Repositories\OrderRepository;

/**
 * Profitability API Controller
 *
 * Provides profitability calculation and aggregation endpoints
 * for order analysis.
 */
class ProfitabilityController extends Controller
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
     * Calculate profitability for a specific order.
     *
     * @param  int  $orderId
     * @return JsonResponse
     */
    public function calculate(int $orderId): JsonResponse
    {
        try {
            $order = $this->orderRepository->findOrFail($orderId);

            $revenue = (float) $order->total_amount;
            $cost = 0;

            // Calculate cost from order items
            foreach ($order->items as $item) {
                if ($item->product) {
                    $values = $item->product->values;
                    $productCost = (float) ($values['common']['cost'] ?? 0);
                    $cost += $productCost * $item->quantity;
                }
            }

            $profit = $revenue - $cost;
            $marginPercentage = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            return response()->json([
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'profit' => round($profit, 2),
                    'margin_percentage' => round($marginPercentage, 2),
                    'currency' => $order->currency_code ?? 'USD',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.api.order-not-found'),
            ], 404);
        }
    }

    /**
     * Aggregate profitability by channel.
     *
     * @param  Request  $request
     * @param  int  $channelId
     * @return JsonResponse
     */
    public function aggregateByChannel(Request $request, int $channelId): JsonResponse
    {
        $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        try {
            $query = $this->orderRepository->query()
                ->where('channel_id', $channelId);

            if ($request->has('start_date')) {
                $query->where('order_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('order_date', '<=', $request->end_date);
            }

            $orders = $query->get();

            $totalRevenue = 0;
            $totalCost = 0;

            foreach ($orders as $order) {
                $totalRevenue += (float) $order->total_amount;

                foreach ($order->items as $item) {
                    if ($item->product) {
                        $values = $item->product->values;
                        $productCost = (float) ($values['common']['cost'] ?? 0);
                        $totalCost += $productCost * $item->quantity;
                    }
                }
            }

            $totalProfit = $totalRevenue - $totalCost;
            $marginPercentage = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

            return response()->json([
                'data' => [
                    'channel_id' => $channelId,
                    'total_orders' => $orders->count(),
                    'total_revenue' => round($totalRevenue, 2),
                    'total_cost' => round($totalCost, 2),
                    'total_profit' => round($totalProfit, 2),
                    'margin_percentage' => round($marginPercentage, 2),
                    'date_range' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.api.calculation-failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
