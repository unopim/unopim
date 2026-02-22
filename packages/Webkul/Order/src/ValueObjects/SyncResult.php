<?php

namespace Webkul\Order\ValueObjects;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Webkul\Order\Models\OrderSyncLog;
use Webkul\Order\Models\UnifiedOrder;

/**
 * Immutable value object representing the result of an order synchronization operation.
 *
 * Encapsulates success/failure status, the synchronized order (if successful),
 * the sync log entry, and any errors that occurred during synchronization.
 */
final readonly class SyncResult implements Arrayable, JsonSerializable
{
    /**
     * @param  bool  $success            Whether the sync operation succeeded.
     * @param  UnifiedOrder|null  $order  The synchronized order (null if failed).
     * @param  OrderSyncLog|null  $syncLog  The sync log entry for this operation.
     * @param  array<string, mixed>  $errors  Array of error messages/details.
     * @param  Carbon  $syncedAt          Timestamp when sync was attempted.
     */
    public function __construct(
        public bool $success,
        public ?UnifiedOrder $order,
        public ?OrderSyncLog $syncLog,
        public array $errors,
        public Carbon $syncedAt,
    ) {}

    /**
     * Create a successful SyncResult.
     *
     * @param  UnifiedOrder  $order      The successfully synchronized order.
     * @param  OrderSyncLog|null  $syncLog  Optional sync log entry.
     * @return self                      New SyncResult instance.
     */
    public static function success(UnifiedOrder $order, ?OrderSyncLog $syncLog = null): self
    {
        return new self(
            success: true,
            order: $order,
            syncLog: $syncLog,
            errors: [],
            syncedAt: Carbon::now(),
        );
    }

    /**
     * Create a failed SyncResult.
     *
     * @param  array<string, mixed>  $errors  Error messages/details.
     * @param  OrderSyncLog|null  $syncLog    Optional sync log entry.
     * @return self                           New SyncResult instance.
     */
    public static function failure(array $errors, ?OrderSyncLog $syncLog = null): self
    {
        return new self(
            success: false,
            order: null,
            syncLog: $syncLog,
            errors: $errors,
            syncedAt: Carbon::now(),
        );
    }

    /**
     * Check if the sync result has any errors.
     *
     * @return bool  True if errors exist.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Get the first error message.
     *
     * @return string|null  First error message or null if no errors.
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstError = reset($this->errors);

        return is_array($firstError) ? ($firstError['message'] ?? json_encode($firstError)) : (string) $firstError;
    }

    /**
     * Get all error messages as a flat array.
     *
     * @return array<string>  Array of error message strings.
     */
    public function getErrorMessages(): array
    {
        return array_map(function ($error) {
            return is_array($error) ? ($error['message'] ?? json_encode($error)) : (string) $error;
        }, $this->errors);
    }

    /**
     * Get the synchronized order ID (if successful).
     *
     * @return int|null  Order ID or null if failed.
     */
    public function getOrderId(): ?int
    {
        return $this->order?->id;
    }

    /**
     * Get the channel order ID from the synchronized order.
     *
     * @return string|null  Channel order ID or null if failed.
     */
    public function getChannelOrderId(): ?string
    {
        return $this->order?->channel_order_id;
    }

    /**
     * Get the sync log ID.
     *
     * @return int|null  Sync log ID or null if no log.
     */
    public function getSyncLogId(): ?int
    {
        return $this->syncLog?->id;
    }

    /**
     * Check if this was a create operation (new order).
     *
     * @return bool  True if order was created (not updated).
     */
    public function wasCreated(): bool
    {
        if (! $this->success || ! $this->order) {
            return false;
        }

        return $this->order->wasRecentlyCreated;
    }

    /**
     * Check if this was an update operation (existing order).
     *
     * @return bool  True if order was updated (not created).
     */
    public function wasUpdated(): bool
    {
        if (! $this->success || ! $this->order) {
            return false;
        }

        return ! $this->order->wasRecentlyCreated;
    }

    /**
     * Get a human-readable status message.
     *
     * @return string  Status message.
     */
    public function getStatusMessage(): string
    {
        if ($this->success) {
            $action = $this->wasCreated() ? 'created' : 'updated';

            return "Order successfully {$action}: {$this->getChannelOrderId()}";
        }

        $errorMsg = $this->getFirstError() ?? 'Unknown error';

        return "Order sync failed: {$errorMsg}";
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'          => $this->success,
            'order_id'         => $this->getOrderId(),
            'channel_order_id' => $this->getChannelOrderId(),
            'sync_log_id'      => $this->getSyncLogId(),
            'errors'           => $this->errors,
            'error_count'      => count($this->errors),
            'synced_at'        => $this->syncedAt->toIso8601String(),
            'was_created'      => $this->wasCreated(),
            'was_updated'      => $this->wasUpdated(),
            'status_message'   => $this->getStatusMessage(),
        ];
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get a compact representation for logging.
     *
     * @return array<string, mixed>  Compact log data.
     */
    public function toLogArray(): array
    {
        return [
            'success'          => $this->success,
            'order_id'         => $this->getOrderId(),
            'channel_order_id' => $this->getChannelOrderId(),
            'error'            => $this->getFirstError(),
            'synced_at'        => $this->syncedAt->toIso8601String(),
        ];
    }
}
