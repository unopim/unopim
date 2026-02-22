<?php

namespace Webkul\Order\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class OrderSyncLogRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Order\Contracts\OrderSyncLog';
    }

    /**
     * Get recent sync logs.
     */
    public function getRecentSyncs(int $limit = 10): Collection
    {
        return $this->model
            ->with(['channel'])
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed sync logs.
     */
    public function getFailedSyncs(): Collection
    {
        return $this->model
            ->failed()
            ->with(['channel'])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get sync statistics.
     */
    public function getSyncStats(int $channelId = null, string $period = '30days'): array
    {
        $query = $this->model->newQuery();

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $startDate = match ($period) {
            '7days'  => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default  => now()->subDays(30),
        };

        $query->where('started_at', '>=', $startDate);

        $totalSyncs = $query->count();
        $successfulSyncs = (clone $query)->where('status', 'success')->count();
        $failedSyncs = (clone $query)->where('status', 'failed')->count();
        $totalOrdersSynced = (clone $query)->sum('orders_synced');

        $avgDuration = $this->model
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->where('started_at', '>=', $startDate)
            ->when($channelId, fn ($q) => $q->where('channel_id', $channelId))
            ->get()
            ->avg(function ($log) {
                return $log->getDuration();
            });

        return [
            'total_syncs'         => $totalSyncs,
            'successful_syncs'    => $successfulSyncs,
            'failed_syncs'        => $failedSyncs,
            'total_orders_synced' => $totalOrdersSynced,
            'success_rate'        => $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100, 2) : 0,
            'avg_duration'        => $avgDuration ? round($avgDuration, 2) : null,
        ];
    }
}
