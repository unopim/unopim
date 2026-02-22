<?php

namespace Webkul\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webkul\Order\Models\Order;

/**
 * Order Status Updated Event
 *
 * Dispatched when an order's status changes, either manually
 * or through synchronization.
 */
class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * The order whose status was updated.
     *
     * @var Order
     */
    public $order;

    /**
     * The previous status.
     *
     * @var string
     */
    public $oldStatus;

    /**
     * The new status.
     *
     * @var string
     */
    public $newStatus;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  string  $oldStatus
     * @return void
     */
    public function __construct(Order $order, string $oldStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $order->status;
    }
}
