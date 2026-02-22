<?php

namespace Webkul\Order\ValueObjects;

use Carbon\Carbon;

/**
 * SyncStatistics
 *
 * Value object representing aggregated statistics for order synchronization operations.
 * Contains metrics about sync frequency, success rates, and performance.
 *
 * @package Webkul\Order\ValueObjects
 */
readonly class SyncStatistics
{
    /**
     * Create a new SyncStatistics instance.
     *
     * @param  int  $channelId  Channel ID
     * @param  int  $totalSyncs  Total number of sync operations
     * @param  int  $successfulSyncs  Number of successful syncs
     * @param  int  $failedSyncs  Number of failed syncs
     * @param  int  $totalOrdersSynced  Total orders synced
     * @param  int  $totalOrdersFailed  Total orders failed
     * @param  float  $averageSyncDuration  Average sync duration in seconds
     * @param  Carbon|null  $lastSyncAt  Last sync timestamp
     * @param  Carbon|null  $firstSyncAt  First sync timestamp
     * @param  array  $dateRange  Date range for statistics
     */
    public function __construct(
        public int $channelId,
        public int $totalSyncs,
        public int $successfulSyncs,
        public int $failedSyncs,
        public int $totalOrdersSynced,
        public int $totalOrdersFailed,
        public float $averageSyncDuration,
        public ?Carbon $lastSyncAt = null,
        public ?Carbon $firstSyncAt = null,
        public array $dateRange = []
    ) {}

    /**
     * Get sync success rate as percentage.
     *
     * @return float
     */
    public function getSyncSuccessRate(): float
    {
        return $this->totalSyncs > 0
            ? round(($this->successfulSyncs / $this->totalSyncs) * 100, 2)
            : 0;
    }

    /**
     * Get order success rate as percentage.
     *
     * @return float
     */
    public function getOrderSuccessRate(): float
    {
        $totalOrders = $this->totalOrdersSynced + $this->totalOrdersFailed;

        return $totalOrders > 0
            ? round(($this->totalOrdersSynced / $totalOrders) * 100, 2)
            : 0;
    }

    /**
     * Get average orders per sync.
     *
     * @return float
     */
    public function getAverageOrdersPerSync(): float
    {
        return $this->successfulSyncs > 0
            ? round($this->totalOrdersSynced / $this->successfulSyncs, 2)
            : 0;
    }

    /**
     * Get average failed orders per sync.
     *
     * @return float
     */
    public function getAverageFailedOrdersPerSync(): float
    {
        return $this->totalSyncs > 0
            ? round($this->totalOrdersFailed / $this->totalSyncs, 2)
            : 0;
    }

    /**
     * Get time since last sync in hours.
     *
     * @return float|null
     */
    public function getHoursSinceLastSync(): ?float
    {
        if (! $this->lastSyncAt) {
            return null;
        }

        return round($this->lastSyncAt->diffInHours(now(), false), 2);
    }

    /**
     * Check if sync is healthy (success rate >= 95%).
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->getSyncSuccessRate() >= 95.0;
    }

    /**
     * Check if sync needs attention (success rate < 80%).
     *
     * @return bool
     */
    public function needsAttention(): bool
    {
        return $this->getSyncSuccessRate() < 80.0;
    }

    /**
     * Get health status.
     *
     * @return string
     */
    public function getHealthStatus(): string
    {
        return match (true) {
            $this->isHealthy() => 'healthy',
            $this->needsAttention() => 'critical',
            default => 'warning'
        };
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'channel_id' => $this->channelId,
            'date_range' => $this->dateRange,
            'sync_operations' => [
                'total' => $this->totalSyncs,
                'successful' => $this->successfulSyncs,
                'failed' => $this->failedSyncs,
                'success_rate' => $this->getSyncSuccessRate(),
            ],
            'orders' => [
                'total_synced' => $this->totalOrdersSynced,
                'total_failed' => $this->totalOrdersFailed,
                'success_rate' => $this->getOrderSuccessRate(),
                'average_per_sync' => $this->getAverageOrdersPerSync(),
                'average_failed_per_sync' => $this->getAverageFailedOrdersPerSync(),
            ],
            'performance' => [
                'average_duration_seconds' => $this->averageSyncDuration,
                'average_duration_minutes' => round($this->averageSyncDuration / 60, 2),
            ],
            'timestamps' => [
                'first_sync' => $this->firstSyncAt?->toIso8601String(),
                'last_sync' => $this->lastSyncAt?->toIso8601String(),
                'hours_since_last' => $this->getHoursSinceLastSync(),
            ],
            'health' => [
                'status' => $this->getHealthStatus(),
                'is_healthy' => $this->isHealthy(),
                'needs_attention' => $this->needsAttention(),
            ],
        ];
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

    /**
     * Get summary for dashboard display.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'health_status' => $this->getHealthStatus(),
            'sync_success_rate' => $this->getSyncSuccessRate().'%',
            'total_orders_synced' => $this->totalOrdersSynced,
            'last_sync' => $this->lastSyncAt?->diffForHumans() ?? 'Never',
            'average_duration' => round($this->averageSyncDuration / 60, 1).' min',
        ];
    }
}
