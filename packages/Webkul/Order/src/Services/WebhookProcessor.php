<?php

namespace Webkul\Order\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Webkul\Order\Events\WebhookReceived;
use Webkul\Order\Models\OrderWebhook;
use Webkul\Order\ValueObjects\WebhookProcessResult;

/**
 * WebhookProcessor
 *
 * Processes incoming webhook events from external channels (Salla, Shopify, WooCommerce).
 * Verifies HMAC signatures, dispatches events, and triggers appropriate sync operations.
 *
 * @package Webkul\Order\Services
 */
class WebhookProcessor
{
    /**
     * Create a new WebhookProcessor instance.
     *
     * @param  OrderSyncService  $syncService
     */
    public function __construct(
        protected OrderSyncService $syncService
    ) {}

    /**
     * Process incoming webhook event.
     *
     * @param  string  $channelCode
     * @param  array  $payload
     * @param  array  $headers
     * @return WebhookProcessResult
     *
     * @throws Exception
     */
    public function process(string $channelCode, array $payload, array $headers = []): WebhookProcessResult
    {
        $webhook = OrderWebhook::where('channel_code', $channelCode)
            ->where('is_active', true)
            ->first();

        if (! $webhook) {
            throw new Exception("No active webhook configured for channel: {$channelCode}");
        }

        // Verify HMAC signature
        if (! $this->verifySignature($webhook, $payload, $headers)) {
            Log::warning('Webhook signature verification failed', [
                'channel' => $channelCode,
                'headers' => $headers,
            ]);

            throw new Exception('Webhook signature verification failed');
        }

        // Update last triggered timestamp
        $webhook->update(['last_triggered_at' => now()]);

        // Process event based on type
        $eventType = $this->extractEventType($payload, $headers);

        event(new WebhookReceived($webhook, $eventType, $payload));

        $result = $this->processEvent($webhook, $eventType, $payload);

        return new WebhookProcessResult(
            success: $result['success'],
            eventType: $eventType,
            processedOrders: $result['processed_orders'] ?? 0,
            message: $result['message'] ?? 'Webhook processed successfully',
            webhookId: $webhook->id,
            channelCode: $channelCode,
            processedAt: now()
        );
    }

    /**
     * Verify HMAC signature.
     *
     * @param  OrderWebhook  $webhook
     * @param  array  $payload
     * @param  array  $headers
     * @return bool
     */
    protected function verifySignature(OrderWebhook $webhook, array $payload, array $headers): bool
    {
        if (! $webhook->secret_key) {
            return true; // No signature verification configured
        }

        $signature = $headers['X-Webhook-Signature']
            ?? $headers['x-webhook-signature']
            ?? $headers['X-Shopify-Hmac-SHA256']
            ?? $headers['x-shopify-hmac-sha256']
            ?? null;

        if (! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', json_encode($payload), $webhook->secret_key);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Extract event type from payload/headers.
     *
     * @param  array  $payload
     * @param  array  $headers
     * @return string
     */
    protected function extractEventType(array $payload, array $headers): string
    {
        return $headers['X-Event-Type']
            ?? $headers['x-event-type']
            ?? $headers['X-Shopify-Topic']
            ?? $headers['x-shopify-topic']
            ?? $payload['event_type']
            ?? $payload['type']
            ?? 'order.created';
    }

    /**
     * Process specific event type.
     *
     * @param  OrderWebhook  $webhook
     * @param  string  $eventType
     * @param  array  $payload
     * @return array
     */
    protected function processEvent(OrderWebhook $webhook, string $eventType, array $payload): array
    {
        return match (true) {
            str_contains($eventType, 'order.created'),
            str_contains($eventType, 'orders/create') => $this->handleOrderCreated($webhook, $payload),

            str_contains($eventType, 'order.updated'),
            str_contains($eventType, 'orders/updated') => $this->handleOrderUpdated($webhook, $payload),

            str_contains($eventType, 'order.cancelled'),
            str_contains($eventType, 'orders/cancelled') => $this->handleOrderCancelled($webhook, $payload),

            str_contains($eventType, 'order.fulfilled'),
            str_contains($eventType, 'orders/fulfilled') => $this->handleOrderFulfilled($webhook, $payload),

            str_contains($eventType, 'order.paid'),
            str_contains($eventType, 'orders/paid') => $this->handleOrderPaid($webhook, $payload),

            default => ['success' => true, 'message' => 'Event type not handled', 'processed_orders' => 0]
        };
    }

    /**
     * Handle order created event.
     *
     * @param  OrderWebhook  $webhook
     * @param  array  $payload
     * @return array
     */
    protected function handleOrderCreated(OrderWebhook $webhook, array $payload): array
    {
        try {
            $orderData = $this->normalizeOrderData($payload);
            $channel = $webhook->channel;

            $order = $this->syncService->syncOrder($channel, $orderData);

            Log::info('Order created via webhook', [
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
                'channel_order_id' => $orderData['id'],
            ]);

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'processed_orders' => 1,
                'order_id' => $order->id,
            ];
        } catch (Exception $e) {
            Log::error('Failed to process order.created webhook', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed_orders' => 0,
            ];
        }
    }

    /**
     * Handle order updated event.
     *
     * @param  OrderWebhook  $webhook
     * @param  array  $payload
     * @return array
     */
    protected function handleOrderUpdated(OrderWebhook $webhook, array $payload): array
    {
        try {
            $orderData = $this->normalizeOrderData($payload);
            $channel = $webhook->channel;

            $order = $this->syncService->syncOrder($channel, $orderData);

            Log::info('Order updated via webhook', [
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'message' => 'Order updated successfully',
                'processed_orders' => 1,
                'order_id' => $order->id,
            ];
        } catch (Exception $e) {
            Log::error('Failed to process order.updated webhook', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed_orders' => 0,
            ];
        }
    }

    /**
     * Handle order cancelled event.
     *
     * @param  OrderWebhook  $webhook
     * @param  array  $payload
     * @return array
     */
    protected function handleOrderCancelled(OrderWebhook $webhook, array $payload): array
    {
        try {
            $orderData = $this->normalizeOrderData($payload);
            $orderData['status'] = 'cancelled';

            $channel = $webhook->channel;
            $order = $this->syncService->syncOrder($channel, $orderData);

            Log::info('Order cancelled via webhook', [
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'message' => 'Order cancelled successfully',
                'processed_orders' => 1,
                'order_id' => $order->id,
            ];
        } catch (Exception $e) {
            Log::error('Failed to process order.cancelled webhook', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed_orders' => 0,
            ];
        }
    }

    /**
     * Handle order fulfilled event.
     *
     * @param  OrderWebhook  $webhook
     * @param  array  $payload
     * @return array
     */
    protected function handleOrderFulfilled(OrderWebhook $webhook, array $payload): array
    {
        try {
            $orderData = $this->normalizeOrderData($payload);
            $orderData['status'] = 'completed';

            $channel = $webhook->channel;
            $order = $this->syncService->syncOrder($channel, $orderData);

            Log::info('Order fulfilled via webhook', [
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'message' => 'Order fulfilled successfully',
                'processed_orders' => 1,
                'order_id' => $order->id,
            ];
        } catch (Exception $e) {
            Log::error('Failed to process order.fulfilled webhook', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed_orders' => 0,
            ];
        }
    }

    /**
     * Handle order paid event.
     *
     * @param  OrderWebhook  $webhook
     * @param  array  $payload
     * @return array
     */
    protected function handleOrderPaid(OrderWebhook $webhook, array $payload): array
    {
        try {
            $orderData = $this->normalizeOrderData($payload);
            $orderData['payment_status'] = 'paid';

            $channel = $webhook->channel;
            $order = $this->syncService->syncOrder($channel, $orderData);

            Log::info('Order paid via webhook', [
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'message' => 'Order payment updated successfully',
                'processed_orders' => 1,
                'order_id' => $order->id,
            ];
        } catch (Exception $e) {
            Log::error('Failed to process order.paid webhook', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed_orders' => 0,
            ];
        }
    }

    /**
     * Normalize order data from different channel formats.
     *
     * @param  array  $payload
     * @return array
     */
    protected function normalizeOrderData(array $payload): array
    {
        // Extract the order data (some webhooks wrap it in 'data' or 'order' key)
        $order = $payload['data'] ?? $payload['order'] ?? $payload;

        return [
            'id' => $order['id'] ?? $order['order_id'] ?? null,
            'order_number' => $order['order_number'] ?? $order['name'] ?? $order['number'] ?? null,
            'customer' => [
                'name' => $order['customer']['name'] ?? $order['billing_address']['name'] ?? 'Guest',
                'email' => $order['customer']['email'] ?? $order['email'] ?? null,
                'phone' => $order['customer']['phone'] ?? $order['billing_address']['phone'] ?? null,
            ],
            'status' => $order['status'] ?? $order['financial_status'] ?? 'pending',
            'payment_status' => $order['payment_status'] ?? $order['financial_status'] ?? 'pending',
            'total' => $order['total'] ?? $order['total_price'] ?? 0,
            'currency' => $order['currency'] ?? $order['currency_code'] ?? 'USD',
            'shipping_address' => $order['shipping_address'] ?? [],
            'billing_address' => $order['billing_address'] ?? [],
            'created_at' => $order['created_at'] ?? now(),
        ];
    }
}
