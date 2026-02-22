<?php

namespace Webkul\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Order\Models\Order;
use Webkul\Order\Models\OrderSyncLog;

/**
 * Order Synced Event
 *
 * Dispatched after a successful order synchronization from a channel.
 * Used to trigger post-sync operations like profitability calculation.
 */
class OrderSynced
{
    use Dispatchable, SerializesModels;

    /**
     * The order that was synced.
     *
     * @var Order
     */
    public $order;

    /**
     * The sync log entry.
     *
     * @var OrderSyncLog
     */
    public $syncLog;

    /**
     * Whether this is a new order or an update.
     *
     * @var bool
     */
    public $isNew;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  OrderSyncLog  $syncLog
     * @param  bool  $isNew
     * @return void
     */
    public function __construct(Order $order, OrderSyncLog $syncLog, bool $isNew = true)
    {
        $this->order = $order;
        $this->syncLog = $syncLog;
        $this->isNew = $isNew;
    }
}
