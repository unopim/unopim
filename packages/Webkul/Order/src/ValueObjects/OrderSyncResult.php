<?php

namespace Webkul\Order\ValueObjects;

use Carbon\Carbon;

/**
 * OrderSyncResult
 *
 * Value object representing the result of an order synchronization operation.
 * Contains statistics about synced/failed orders and error details.
 *
 * @package Webkul\Order\ValueObjects
 */
readonly class OrderSyncResult
{
    /**
     * Create a new OrderSyncResult instance.
     *
     * @param  bool  $success  Whether the sync operation was successful
     * @param  int  $syncedCount  Number of orders successfully synced
     * @param  int  $failedCount  Number of orders that failed to sync
     * @param  int  $totalProcessed  Total number of orders processed
     * @param  Carbon  $startedAt  When the sync started
     * @param  Carbon  $completedAt  When the sync completed
     * @param  array  $errors  Array of error details
     * @param  int|null  $syncLogId  ID of the sync log entry
     */
    public function __construct(
        public bool $success,
        public int $syncedCount,
        public int $failedCount,
        public int $totalProcessed,
        public Carbon $startedAt,
        public Carbon $completedAt,
        public array $errors = [],
        public ?int $syncLogId = null
    ) {}

    /**
     * Get duration of sync operation in seconds.
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->completedAt->diffInSeconds($this->startedAt);
    }

    /**
     * Get success rate as percentage.
     *
     * @return float
     */
    public function getSuccessRate(): float
    {
        if ($this->totalProcessed === 0) {
            return 0.0;
        }

        return round(($this->syncedCount / $this->totalProcessed) * 100, 2);
    }

    /**
     * Check if there were any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'synced_count' => $this->syncedCount,
            'failed_count' => $this->failedCount,
            'total_processed' => $this->totalProcessed,
            'success_rate' => $this->getSuccessRate(),
            'duration_seconds' => $this->getDuration(),
            'errors' => $this->errors,
            'sync_log_id' => $this->syncLogId,
            'started_at' => $this->startedAt->toIso8601String(),
            'completed_at' => $this->completedAt->toIso8601String(),
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
}
