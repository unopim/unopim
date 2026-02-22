<?php

namespace Webkul\Order\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class OrderWebhookRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Order\Contracts\OrderWebhook';
    }

    /**
     * Get active webhooks.
     */
    public function getActiveWebhooks(int $channelId = null, string $eventType = null): Collection
    {
        $query = $this->model
            ->active()
            ->with(['channel']);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        return $query->get();
    }

    /**
     * Get webhook by URL.
     */
    public function getByUrl(string $url)
    {
        return $this->model
            ->where('webhook_url', $url)
            ->first();
    }

    /**
     * Update trigger statistics.
     */
    public function updateTriggerStats(int $id, bool $success = true): void
    {
        $webhook = $this->find($id);

        if (! $webhook) {
            return;
        }

        if ($success) {
            $webhook->recordSuccess();
        } else {
            $webhook->recordFailure();
        }
    }
}
