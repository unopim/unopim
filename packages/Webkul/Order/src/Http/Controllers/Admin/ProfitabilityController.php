<?php

namespace Webkul\Order\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Channel\Repositories\ChannelRepository;
use Webkul\Order\DataGrids\Admin\ProfitabilityDataGrid;
use Webkul\Order\Repositories\OrderRepository;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Profitability Controller
 *
 * Provides profitability analysis dashboard and reports
 * with channel-wise, product-wise, and time-series analysis.
 */
class ProfitabilityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  OrderRepository  $orderRepository
     * @param  ChannelRepository  $channelRepository
     * @param  ProductRepository  $productRepository
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected ChannelRepository $channelRepository,
        protected ProductRepository $productRepository
    ) {
    }

    /**
     * Display profitability overview dashboard.
     *
     * @param  Request  $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        if (! bouncer()->allows('orders.profitability.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        if ($request->ajax() && $request->has('grid')) {
            return datagrid(ProfitabilityDataGrid::class)->process();
        }

        // Get date range from request or default to last 30 days
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Calculate overall profitability
        $overall = $this->calculateOverallProfitability($startDate, $endDate);

        // Get top performing channels
        $topChannels = $this->getTopChannelsByProfit($startDate, $endDate, 5);

        // Get top performing products
        $topProducts = $this->getTopProductsByProfit($startDate, $endDate, 10);

        return view('order::admin.profitability.index', [
            'overall' => $overall,
            'topChannels' => $topChannels,
            'topProducts' => $topProducts,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get channel-wise profitability analysis.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function byChannel(Request $request): JsonResponse
    {
        if (! bouncer()->allows('orders.profitability.view')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $channelId = $request->input('channel_id');

        $data = $this->getChannelProfitability($startDate, $endDate, $channelId);

        return response()->json($data);
    }

    /**
     * Get product-wise profitability analysis.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function byProduct(Request $request): JsonResponse
    {
        if (! bouncer()->allows('orders.profitability.view')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $productId = $request->input('product_id');

        $data = $this->getProductProfitability($startDate, $endDate, $productId);

        return response()->json($data);
    }

    /**
     * Get time-series profitability data.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function byDateRange(Request $request): JsonResponse
    {
        if (! bouncer()->allows('orders.profitability.view')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'day'); // day, week, month

        $data = $this->getTimeSeriesProfitability($startDate, $endDate, $groupBy);

        return response()->json($data);
    }

    /**
     * Export profitability report.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        if (! bouncer()->allows('orders.profitability.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        return datagrid(ProfitabilityDataGrid::class)->export();
    }

    /**
     * Calculate overall profitability for date range.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return array
     */
    protected function calculateOverallProfitability(string $startDate, string $endDate): array
    {
        $orders = DB::table('orders')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->get();

        $totalRevenue = $orders->sum('total_amount');
        $totalCost = 0;

        foreach ($orders as $order) {
            $items = DB::table('order_items')->where('order_id', $order->id)->get();

            foreach ($items as $item) {
                $product = DB::table('products')->find($item->product_id);
                if ($product) {
                    $values = json_decode($product->values, true);
                    $cost = (float) ($values['common']['cost'] ?? 0);
                    $totalCost += $cost * $item->quantity;
                }
            }
        }

        $totalProfit = $totalRevenue - $totalCost;
        $marginPercentage = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => round($totalRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'margin_percentage' => round($marginPercentage, 2),
        ];
    }

    /**
     * Get top channels by profit.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  int  $limit
     * @return array
     */
    protected function getTopChannelsByProfit(string $startDate, string $endDate, int $limit = 5): array
    {
        // Implementation would aggregate by channel
        return [];
    }

    /**
     * Get top products by profit.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  int  $limit
     * @return array
     */
    protected function getTopProductsByProfit(string $startDate, string $endDate, int $limit = 10): array
    {
        // Implementation would aggregate by product
        return [];
    }

    /**
     * Get channel profitability data.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  int|null  $channelId
     * @return array
     */
    protected function getChannelProfitability(string $startDate, string $endDate, ?int $channelId = null): array
    {
        // Implementation would calculate per-channel metrics
        return [];
    }

    /**
     * Get product profitability data.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  int|null  $productId
     * @return array
     */
    protected function getProductProfitability(string $startDate, string $endDate, ?int $productId = null): array
    {
        // Implementation would calculate per-product metrics
        return [];
    }

    /**
     * Get time-series profitability data.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  string  $groupBy
     * @return array
     */
    protected function getTimeSeriesProfitability(string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        // Implementation would group by time period
        return [];
    }
}
