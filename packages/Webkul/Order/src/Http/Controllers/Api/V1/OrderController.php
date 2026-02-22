<?php

namespace Webkul\Order\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\AdminApi\Http\Controllers\V1\Controller;
use Webkul\Order\Http\Resources\V1\OrderResource;
use Webkul\Order\Repositories\OrderRepository;

/**
 * Order API Controller
 *
 * RESTful API for order management with authentication,
 * pagination, filtering, and status updates.
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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'channel_id' => 'integer|exists:channels,id',
            'status' => 'string|in:pending,processing,completed,cancelled,refunded',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'sort_by' => 'string|in:order_date,total_amount,status',
            'sort_order' => 'string|in:asc,desc',
        ]);

        $query = $this->orderRepository->query();

        // Apply filters
        if ($request->has('channel_id')) {
            $query->where('channel_id', $request->channel_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->where('order_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('order_date', '<=', $request->end_date);
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'order_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $limit = $request->input('limit', 25);
        $orders = $query->paginate($limit);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
            'links' => [
                'first' => $orders->url(1),
                'last' => $orders->url($orders->lastPage()),
                'prev' => $orders->previousPageUrl(),
                'next' => $orders->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderRepository->findOrFail($id);

            return response()->json([
                'data' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.api.order-not-found'),
            ], 404);
        }
    }

    /**
     * Update order status.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,refunded',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $order = $this->orderRepository->findOrFail($id);

            $this->orderRepository->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ], $id);

            return response()->json([
                'message' => trans('order::app.api.status-updated'),
                'data' => new OrderResource($order->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('order::app.api.update-failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
