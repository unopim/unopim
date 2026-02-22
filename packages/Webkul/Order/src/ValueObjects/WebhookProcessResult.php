<?php

namespace Webkul\Order\ValueObjects;

use Carbon\Carbon;

/**
 * WebhookProcessResult
 *
 * Value object representing the result of a webhook processing operation.
 * Contains success status, event type, and processing details.
 *
 * @package Webkul\Order\ValueObjects
 */
readonly class WebhookProcessResult
{
    /**
     * Create a new WebhookProcessResult instance.
     *
     * @param  bool  $success  Whether the webhook was processed successfully
     * @param  string  $eventType  Type of webhook event
     * @param  int  $processedOrders  Number of orders processed
     * @param  string  $message  Result message
     * @param  int  $webhookId  Webhook configuration ID
     * @param  string  $channelCode  Channel code
     * @param  Carbon  $processedAt  When the webhook was processed
     */
    public function __construct(
        public bool $success,
        public string $eventType,
        public int $processedOrders,
        public string $message,
        public int $webhookId,
        public string $channelCode,
        public Carbon $processedAt
    ) {}

    /**
     * Check if any orders were processed.
     *
     * @return bool
     */
    public function hasProcessedOrders(): bool
    {
        return $this->processedOrders > 0;
    }

    /**
     * Check if this is a creation event.
     *
     * @return bool
     */
    public function isCreationEvent(): bool
    {
        return str_contains($this->eventType, 'created') || str_contains($this->eventType, 'create');
    }

    /**
     * Check if this is an update event.
     *
     * @return bool
     */
    public function isUpdateEvent(): bool
    {
        return str_contains($this->eventType, 'updated') || str_contains($this->eventType, 'update');
    }

    /**
     * Check if this is a cancellation event.
     *
     * @return bool
     */
    public function isCancellationEvent(): bool
    {
        return str_contains($this->eventType, 'cancelled') || str_contains($this->eventType, 'canceled');
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'event_type' => $this->eventType,
            'processed_orders' => $this->processedOrders,
            'message' => $this->message,
            'webhook_id' => $this->webhookId,
            'channel_code' => $this->channelCode,
            'processed_at' => $this->processedAt->toIso8601String(),
            'event_category' => $this->getEventCategory(),
        ];
    }

    /**
     * Get event category.
     *
     * @return string
     */
    public function getEventCategory(): string
    {
        return match (true) {
            $this->isCreationEvent() => 'creation',
            $this->isUpdateEvent() => 'update',
            $this->isCancellationEvent() => 'cancellation',
            default => 'other'
        };
    }

    /**
     * Convert to JSON representation.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
