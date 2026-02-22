<?php

namespace Webkul\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Channel\Models\Channel;
use Webkul\Order\Models\OrderSyncLog;

/**
 * Sync Failed Event
 *
 * Dispatched when an order synchronization fails.
 * Used to trigger notifications and error handling.
 */
class SyncFailed
{
    use Dispatchable, SerializesModels;

    /**
     * The channel where sync failed.
     *
     * @var Channel
     */
    public $channel;

    /**
     * The sync log entry.
     *
     * @var OrderSyncLog
     */
    public $syncLog;

    /**
     * The exception that caused the failure.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  Channel  $channel
     * @param  OrderSyncLog  $syncLog
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct(Channel $channel, OrderSyncLog $syncLog, \Exception $exception)
    {
        $this->channel = $channel;
        $this->syncLog = $syncLog;
        $this->exception = $exception;
    }
}
