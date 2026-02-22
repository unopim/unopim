<?php

namespace Webkul\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Webhook Received Event
 *
 * Dispatched when a webhook event is received from an external channel.
 * Used to process the webhook payload asynchronously.
 */
class WebhookReceived
{
    use Dispatchable, SerializesModels;

    /**
     * The channel code that sent the webhook.
     *
     * @var string
     */
    public $channelCode;

    /**
     * The webhook payload.
     *
     * @var array
     */
    public $payload;

    /**
     * The event type.
     *
     * @var string|null
     */
    public $eventType;

    /**
     * Create a new event instance.
     *
     * @param  string  $channelCode
     * @param  array  $payload
     * @param  string|null  $eventType
     * @return void
     */
    public function __construct(string $channelCode, array $payload, ?string $eventType = null)
    {
        $this->channelCode = $channelCode;
        $this->payload = $payload;
        $this->eventType = $eventType;
    }
}
