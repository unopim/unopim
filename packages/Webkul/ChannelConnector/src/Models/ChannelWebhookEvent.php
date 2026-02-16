<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\ChannelConnector\Contracts\ChannelWebhookEvent as ChannelWebhookEventContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ChannelWebhookEvent extends Model implements ChannelWebhookEventContract
{
    use BelongsToTenant;

    protected $table = 'channel_webhook_events';

    protected $fillable = [
        'channel_connector_id',
        'webhook_event_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    public function connector()
    {
        return $this->belongsTo(ChannelConnector::class, 'channel_connector_id');
    }

    /**
     * Check if webhook event was already processed
     */
    public static function isProcessed(int $connectorId, string $webhookEventId): bool
    {
        return static::where('channel_connector_id', $connectorId)
            ->where('webhook_event_id', $webhookEventId)
            ->whereNotNull('processed_at')
            ->exists();
    }

    /**
     * Mark webhook event as processed
     */
    public static function markAsProcessed(int $connectorId, string $webhookEventId, string $eventType): self
    {
        return static::create([
            'channel_connector_id' => $connectorId,
            'webhook_event_id'     => $webhookEventId,
            'event_type'           => $eventType,
            'payload'              => request()->payload()->all(),
            'processed_at'         => now(),
        ]);
    }
}
