<?php

namespace Webkul\Pricing\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class MarginProtectionEventRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\Pricing\Contracts\MarginProtectionEvent::class;
    }

    /**
     * Get all margin protection events that are pending review (blocked and not yet approved/expired).
     */
    public function getPendingEvents(): Collection
    {
        return $this->model
            ->where('event_type', 'blocked')
            ->whereNull('approved_by')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with(['product', 'channel', 'approver'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get all margin protection events for a specific product.
     */
    public function getEventsForProduct(int $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->with(['product', 'channel', 'approver'])
            ->orderByDesc('created_at')
            ->get();
    }
}
