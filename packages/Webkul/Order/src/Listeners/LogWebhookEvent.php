<?php

namespace Webkul\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Webkul\Order\Events\WebhookReceived;

/**
 * Log Webhook Event Listener
 *
 * Logs all webhook events for audit trail and debugging.
 * Runs asynchronously in the queue.
 */
class LogWebhookEvent implements ShouldQueue
{
    /**
     * The name of the queue connection to use.
     *
     * @var string
     */
    public $connection = 'database';

    /**
     * The name of the queue to use.
     *
     * @var string
     */
    public $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  WebhookReceived  $event
     * @return void
     */
    public function handle(WebhookReceived $event): void
    {
        // Find channel by code
        $channel = DB::table('channels')
            ->where('code', $event->channelCode)
            ->first();

        if (! $channel) {
            return;
        }

        // Create webhook log entry
        DB::table('webhook_logs')->insert([
            'channel_id' => $channel->id,
            'event_type' => $event->eventType ?? 'unknown',
            'payload' => json_encode($event->payload),
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update webhook last_triggered_at if webhook config exists
        $webhook = DB::table('webhooks')
            ->where('channel_id', $channel->id)
            ->whereJsonContains('event_types', $event->eventType)
            ->first();

        if ($webhook) {
            DB::table('webhooks')
                ->where('id', $webhook->id)
                ->update([
                    'last_triggered_at' => now(),
                ]);
        }
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param  WebhookReceived  $event
     * @return bool
     */
    public function shouldQueue(WebhookReceived $event): bool
    {
        return true;
    }
}
