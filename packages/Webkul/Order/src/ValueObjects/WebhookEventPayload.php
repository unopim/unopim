<?php

namespace Webkul\Order\ValueObjects;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use JsonSerializable;

/**
 * Immutable value object representing a webhook event payload.
 *
 * Encapsulates all data from an incoming webhook request, including event type,
 * channel information, order data, signature, and timestamp. Provides factory
 * methods for creating instances from HTTP requests.
 */
final readonly class WebhookEventPayload implements Arrayable, JsonSerializable
{
    /**
     * @param  string  $eventType        The webhook event type (e.g., order.created).
     * @param  string  $channelOrderId   The order ID from the external channel.
     * @param  int     $channelId        The internal channel ID.
     * @param  array<string, mixed>  $orderData  The complete order data from channel.
     * @param  string|null  $signature   Optional webhook signature for verification.
     * @param  Carbon  $timestamp        When the webhook event occurred.
     */
    public function __construct(
        public string $eventType,
        public string $channelOrderId,
        public int $channelId,
        public array $orderData,
        public ?string $signature,
        public Carbon $timestamp,
    ) {}

    /**
     * Create a WebhookEventPayload from an HTTP request.
     *
     * Extracts webhook data from request body and headers following common
     * webhook conventions (Shopify, Salla, etc.).
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return self               New WebhookEventPayload instance.
     *
     * @throws \InvalidArgumentException  If required fields are missing.
     */
    public static function fromRequest(Request $request): self
    {
        $payload = $request->all();

        // Extract event type (various header names supported)
        $eventType = $request->header('X-Event-Type')
            ?? $request->header('X-Webhook-Event')
            ?? $request->header('X-Shopify-Topic')
            ?? $payload['event_type']
            ?? throw new \InvalidArgumentException('Missing event_type in webhook request');

        // Extract channel order ID
        $channelOrderId = $payload['order']['channel_order_id']
            ?? $payload['order']['id']
            ?? $payload['channel_order_id']
            ?? throw new \InvalidArgumentException('Missing channel_order_id in webhook payload');

        // Extract channel ID
        $channelId = $payload['channel_id']
            ?? $request->header('X-Channel-Id')
            ?? throw new \InvalidArgumentException('Missing channel_id in webhook request');

        // Extract order data
        $orderData = $payload['order'] ?? $payload;

        // Extract signature (various header names supported)
        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Shopify-Hmac-Sha256')
            ?? $request->header('X-Salla-Signature')
            ?? $payload['signature']
            ?? null;

        // Extract or create timestamp
        $timestamp = isset($payload['timestamp'])
            ? Carbon::parse($payload['timestamp'])
            : Carbon::now();

        return new self(
            eventType: $eventType,
            channelOrderId: (string) $channelOrderId,
            channelId: (int) $channelId,
            orderData: $orderData,
            signature: $signature,
            timestamp: $timestamp,
        );
    }

    /**
     * Create a WebhookEventPayload from an array.
     *
     * Useful for testing or manual webhook processing.
     *
     * @param  array<string, mixed>  $data  The webhook data.
     * @return self                         New WebhookEventPayload instance.
     *
     * @throws \InvalidArgumentException    If required fields are missing.
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['event_type'], $data['channel_order_id'], $data['channel_id'], $data['order_data'])) {
            throw new \InvalidArgumentException('Missing required fields in webhook data array');
        }

        return new self(
            eventType: $data['event_type'],
            channelOrderId: (string) $data['channel_order_id'],
            channelId: (int) $data['channel_id'],
            orderData: $data['order_data'],
            signature: $data['signature'] ?? null,
            timestamp: isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : Carbon::now(),
        );
    }

    /**
     * Check if the payload has a signature for verification.
     *
     * @return bool  True if signature is present.
     */
    public function hasSignature(): bool
    {
        return ! empty($this->signature);
    }

    /**
     * Get the event category (prefix before the dot).
     *
     * For example, "order.created" returns "order".
     *
     * @return string  Event category.
     */
    public function getEventCategory(): string
    {
        $parts = explode('.', $this->eventType, 2);

        return $parts[0] ?? 'unknown';
    }

    /**
     * Get the event action (suffix after the dot).
     *
     * For example, "order.created" returns "created".
     *
     * @return string  Event action.
     */
    public function getEventAction(): string
    {
        $parts = explode('.', $this->eventType, 2);

        return $parts[1] ?? 'unknown';
    }

    /**
     * Check if this is an order-related event.
     *
     * @return bool  True if event category is "order".
     */
    public function isOrderEvent(): bool
    {
        return $this->getEventCategory() === 'order';
    }

    /**
     * Get the raw JSON payload for signature verification.
     *
     * @return string  JSON-encoded order data.
     */
    public function getRawJsonPayload(): string
    {
        return json_encode($this->orderData);
    }

    /**
     * Get customer email from order data.
     *
     * @return string|null  Customer email or null if not present.
     */
    public function getCustomerEmail(): ?string
    {
        return $this->orderData['customer_email']
            ?? $this->orderData['customer']['email']
            ?? null;
    }

    /**
     * Get order total from order data.
     *
     * @return float|null  Order total or null if not present.
     */
    public function getOrderTotal(): ?float
    {
        $total = $this->orderData['total_amount']
            ?? $this->orderData['total']
            ?? $this->orderData['amount']
            ?? null;

        return $total !== null ? (float) $total : null;
    }

    /**
     * Get order currency from order data.
     *
     * @return string|null  Currency code or null if not present.
     */
    public function getCurrencyCode(): ?string
    {
        return $this->orderData['currency_code']
            ?? $this->orderData['currency']
            ?? null;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_type'        => $this->eventType,
            'event_category'    => $this->getEventCategory(),
            'event_action'      => $this->getEventAction(),
            'channel_order_id'  => $this->channelOrderId,
            'channel_id'        => $this->channelId,
            'order_data'        => $this->orderData,
            'signature'         => $this->signature,
            'has_signature'     => $this->hasSignature(),
            'timestamp'         => $this->timestamp->toIso8601String(),
            'customer_email'    => $this->getCustomerEmail(),
            'order_total'       => $this->getOrderTotal(),
            'currency_code'     => $this->getCurrencyCode(),
        ];
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get a compact representation for logging.
     *
     * @return array<string, mixed>  Compact log data.
     */
    public function toLogArray(): array
    {
        return [
            'event_type'       => $this->eventType,
            'channel_order_id' => $this->channelOrderId,
            'channel_id'       => $this->channelId,
            'has_signature'    => $this->hasSignature(),
            'customer_email'   => $this->getCustomerEmail(),
            'order_total'      => $this->getOrderTotal(),
            'timestamp'        => $this->timestamp->toIso8601String(),
        ];
    }
}
