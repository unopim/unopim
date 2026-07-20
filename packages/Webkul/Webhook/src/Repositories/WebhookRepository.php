<?php

namespace Webkul\Webhook\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Webhook\Models\Webhook;

class WebhookRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Webhook::class;
    }

    /**
     * Active webhooks subscribed to the given event key.
     */
    public function getActiveForEvent(string $event): Collection
    {
        return Webhook::query()
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();
    }

    /**
     * Whether any active webhook is subscribed to the given event key.
     */
    public function hasActiveForEvent(string $event): bool
    {
        return Webhook::query()
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->exists();
    }
}
