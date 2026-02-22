<?php

namespace Webkul\Order\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Channel\Repositories\ChannelRepository;
use Webkul\Order\DataGrids\Admin\OrderSyncLogDataGrid;
use Webkul\Order\Jobs\SyncChannelOrders;
use Webkul\Order\Repositories\OrderSyncLogRepository;

/**
 * Order Sync Controller
 *
 * Manages manual order synchronization from external channels
 * and displays sync logs with retry capabilities.
 */
class OrderSyncController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  OrderSyncLogRepository  $syncLogRepository
     * @param  ChannelRepository  $channelRepository
     * @return void
     */
    public function __construct(
        protected OrderSyncLogRepository $syncLogRepository,
        protected ChannelRepository $channelRepository
    ) {
    }

    /**
     * Display sync logs listing.
     *
     * @return View|JsonResponse
     */
    public function index(): View|JsonResponse
    {
        if (! bouncer()->allows('orders.sync.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        if (request()->ajax()) {
            return datagrid(OrderSyncLogDataGrid::class)->process();
        }

        $channels = $this->channelRepository->where('status', 1)->get();

        return view('order::admin.sync.index', [
            'channels' => $channels,
        ]);
    }

    /**
     * Trigger manual sync for a specific channel.
     *
     * @param  int  $channelId
     * @return JsonResponse
     */
    public function syncChannel(int $channelId): JsonResponse
    {
        if (! bouncer()->allows('orders.sync.execute')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        try {
            $channel = $this->channelRepository->findOrFail($channelId);

            if (! $channel->status) {
                return response()->json([
                    'message' => trans('order::app.admin.sync.channel-inactive'),
                ], 422);
            }

            // Create sync log entry
            $syncLog = $this->syncLogRepository->create([
                'channel_id' => $channelId,
                'status' => 'pending',
                'started_at' => now(),
            ]);

            // Dispatch sync job
            SyncChannelOrders::dispatch($channel, $syncLog);

            return response()->json([
                'message' => trans('order::app.admin.sync.sync-initiated', [
                    'channel' => $channel->name,
                ]),
                'sync_log_id' => $syncLog->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Order sync failed for channel: ' . $channelId, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => trans('order::app.admin.sync.sync-failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger sync for all active channels.
     *
     * @return JsonResponse
     */
    public function syncAll(): JsonResponse
    {
        if (! bouncer()->allows('orders.sync.execute')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        try {
            $channels = $this->channelRepository->where('status', 1)->get();

            if ($channels->isEmpty()) {
                return response()->json([
                    'message' => trans('order::app.admin.sync.no-active-channels'),
                ], 422);
            }

            $dispatched = 0;

            foreach ($channels as $channel) {
                $syncLog = $this->syncLogRepository->create([
                    'channel_id' => $channel->id,
                    'status' => 'pending',
                    'started_at' => now(),
                ]);

                SyncChannelOrders::dispatch($channel, $syncLog);
                $dispatched++;
            }

            return response()->json([
                'message' => trans('order::app.admin.sync.sync-all-initiated', [
                    'count' => $dispatched,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Sync all orders failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => trans('order::app.admin.sync.sync-failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display sync log details.
     *
     * @param  int  $id
     * @return View
     */
    public function show(int $id): View
    {
        if (! bouncer()->allows('orders.sync.view')) {
            abort(401, trans('admin::app.errors.401'));
        }

        $syncLog = $this->syncLogRepository->findOrFail($id);

        return view('order::admin.sync.show', [
            'syncLog' => $syncLog,
        ]);
    }

    /**
     * Retry a failed sync.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function retry(int $id): JsonResponse
    {
        if (! bouncer()->allows('orders.sync.execute')) {
            return response()->json([
                'message' => trans('admin::app.errors.401'),
            ], 401);
        }

        try {
            $syncLog = $this->syncLogRepository->findOrFail($id);

            if ($syncLog->status !== 'failed') {
                return response()->json([
                    'message' => trans('order::app.admin.sync.retry-only-failed'),
                ], 422);
            }

            // Update sync log
            $this->syncLogRepository->update([
                'status' => 'pending',
                'started_at' => now(),
                'error_details' => null,
            ], $id);

            // Dispatch sync job
            SyncChannelOrders::dispatch($syncLog->channel, $syncLog->fresh());

            return response()->json([
                'message' => trans('order::app.admin.sync.retry-initiated'),
            ]);
        } catch (\Exception $e) {
            Log::error('Retry sync failed for log: ' . $id, [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => trans('order::app.admin.sync.retry-failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
