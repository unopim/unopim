<?php

namespace Webkul\Pricing\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pricing\Contracts\ChannelCost;

class ChannelCostRepository extends Repository
{
    public function model(): string
    {
        return ChannelCost::class;
    }

    /**
     * Get the currently active channel cost configuration for a given channel.
     */
    public function getActiveForChannel(int $channelId): ?ChannelCost
    {
        $today = now()->toDateString();

        return $this->model
            ->where('channel_id', $channelId)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
