<?php

namespace Webkul\Order\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Webkul\Channel\Models\Channel;
use Webkul\Order\Contracts\OrderSyncLog;
use Webkul\Order\Contracts\UnifiedOrder;
use Webkul\Order\Events\OrderSynced;
use Webkul\Order\Events\SyncFailed;
use Webkul\Order\ValueObjects\OrderSyncResult;

/**
 * OrderSyncService
 *
 * Handles synchronization of orders from external channels (Salla, Shopify, WooCommerce)
 * into the UnoPim unified order system.
 *
 * @package Webkul\Order\Services
 */
class OrderSyncService
{
    /**
     * Create a new OrderSyncService instance.
     *
     * @param  UnifiedOrder  $orderRepository
     * @param  OrderSyncLog  $syncLogRepository
     */
    public function __construct(
        protected UnifiedOrder $orderRepository,
        protected OrderSyncLog $syncLogRepository
    ) {}

    /**
     * Sync orders from a specific channel.
     *
     * @param  int  $channelId
     * @param  array  $options  Options for sync (date_from, date_to, status_filter, etc.)
     * @return OrderSyncResult
     *
     * @throws Exception
     */
    public function syncChannel(int $channelId, array $options = []): OrderSyncResult
    {
        $log = $this->createSyncLog($channelId);

        try {
            $channel = Channel::findOrFail($channelId);
            $adapter = $this->getChannelAdapter($channel);

            $orders = $adapter->fetchOrders($options);
            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($orders as $orderData) {
                try {
                    $this->syncOrder($channel, $orderData);
                    $synced++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = [
                        'order_id' => $orderData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Order sync failed: {$e->getMessage()}", [
                        'channel_id' => $channelId,
                        'order_data' => $orderData,
                    ]);
                }
            }

            $this->completeSyncLog($log, 'completed', $synced, $failed, null, $errors);
            event(new OrderSynced($channel, $synced));

            return new OrderSyncResult(
                success: true,
                syncedCount: $synced,
                failedCount: $failed,
                totalProcessed: $synced + $failed,
                startedAt: $log->started_at,
                completedAt: now(),
                errors: $errors,
                syncLogId: $log->id
            );
        } catch (Exception $e) {
            $this->completeSyncLog($log, 'failed', 0, 0, $e->getMessage());
            event(new SyncFailed($channel, $e));

            throw $e;
        }
    }

    /**
     * Sync a single order from channel data.
     *
     * @param  Channel  $channel
     * @param  array  $orderData
     * @return mixed
     */
    protected function syncOrder(Channel $channel, array $orderData): mixed
    {
        return $this->orderRepository->updateOrCreate(
            [
                'tenant_id' => tenant()->id,
                'channel_id' => $channel->id,
                'channel_order_id' => $orderData['id'],
            ],
            [
                'order_number' => $orderData['order_number'],
                'customer_name' => $orderData['customer']['name'],
                'customer_email' => $orderData['customer']['email'],
                'customer_phone' => $orderData['customer']['phone'] ?? null,
                'status' => $this->mapStatus($orderData['status']),
                'payment_status' => $this->mapPaymentStatus($orderData['payment_status']),
                'total_amount' => $orderData['total'],
                'currency_code' => $orderData['currency'],
                'shipping_address' => $orderData['shipping_address'],
                'billing_address' => $orderData['billing_address'],
                'order_date' => $orderData['created_at'],
                'synced_at' => now(),
            ]
        );
    }

    /**
     * Create a new sync log entry.
     *
     * @param  int  $channelId
     * @return mixed
     */
    protected function createSyncLog(int $channelId): mixed
    {
        return $this->syncLogRepository->create([
            'tenant_id' => tenant()->id,
            'channel_id' => $channelId,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete a sync log entry.
     *
     * @param  mixed  $log
     * @param  string  $status
     * @param  int  $synced
     * @param  int  $failed
     * @param  string|null  $errorMessage
     * @param  array  $errors
     * @return void
     */
    protected function completeSyncLog(
        mixed $log,
        string $status,
        int $synced,
        int $failed,
        ?string $errorMessage = null,
        array $errors = []
    ): void {
        $log->update([
            'status' => $status,
            'orders_synced' => $synced,
            'orders_failed' => $failed,
            'error_message' => $errorMessage,
            'error_details' => !empty($errors) ? json_encode($errors) : null,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get appropriate channel adapter based on channel type.
     *
     * @param  Channel  $channel
     * @return mixed
     *
     * @throws Exception
     */
    protected function getChannelAdapter(Channel $channel): mixed
    {
        return match ($channel->type) {
            'salla' => app(\Webkul\Order\Adapters\SallaOrderAdapter::class),
            'shopify' => app(\Webkul\Order\Adapters\ShopifyOrderAdapter::class),
            'woocommerce' => app(\Webkul\Order\Adapters\WooCommerceOrderAdapter::class),
            default => throw new Exception("Unsupported channel type: {$channel->type}")
        };
    }

    /**
     * Map channel-specific status to unified status.
     *
     * @param  string  $channelStatus
     * @return string
     */
    protected function mapStatus(string $channelStatus): string
    {
        return match (strtolower($channelStatus)) {
            'pending', 'processing', 'awaiting_payment' => 'pending',
            'paid', 'confirmed' => 'processing',
            'shipped', 'in_transit' => 'shipped',
            'delivered', 'completed' => 'completed',
            'cancelled', 'canceled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Map channel-specific payment status to unified payment status.
     *
     * @param  string  $channelPaymentStatus
     * @return string
     */
    protected function mapPaymentStatus(string $channelPaymentStatus): string
    {
        return match (strtolower($channelPaymentStatus)) {
            'pending', 'awaiting', 'unpaid' => 'pending',
            'paid', 'authorized' => 'paid',
            'partially_refunded' => 'partially_refunded',
            'refunded' => 'refunded',
            'failed', 'declined' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Sync multiple channels in batch.
     *
     * @param  array  $channelIds
     * @param  array  $options
     * @return array
     */
    public function syncMultipleChannels(array $channelIds, array $options = []): array
    {
        $results = [];

        foreach ($channelIds as $channelId) {
            try {
                $results[$channelId] = $this->syncChannel($channelId, $options);
            } catch (Exception $e) {
                Log::error("Failed to sync channel {$channelId}: {$e->getMessage()}");
                $results[$channelId] = new OrderSyncResult(
                    success: false,
                    syncedCount: 0,
                    failedCount: 0,
                    totalProcessed: 0,
                    startedAt: now(),
                    completedAt: now(),
                    errors: [['error' => $e->getMessage()]],
                    syncLogId: null
                );
            }
        }

        return $results;
    }

    /**
     * Get sync statistics for a channel.
     *
     * @param  int  $channelId
     * @param  array  $dateRange
     * @return array
     */
    public function getSyncStatistics(int $channelId, array $dateRange = []): array
    {
        $query = $this->syncLogRepository->where('channel_id', $channelId);

        if (!empty($dateRange)) {
            $query->whereBetween('started_at', $dateRange);
        }

        $logs = $query->get();

        return [
            'total_syncs' => $logs->count(),
            'successful_syncs' => $logs->where('status', 'completed')->count(),
            'failed_syncs' => $logs->where('status', 'failed')->count(),
            'total_orders_synced' => $logs->sum('orders_synced'),
            'total_orders_failed' => $logs->sum('orders_failed'),
            'last_sync_at' => $logs->max('completed_at'),
            'average_sync_duration' => $logs->avg(function ($log) {
                return $log->completed_at?->diffInSeconds($log->started_at) ?? 0;
            }),
        ];
    }
}
