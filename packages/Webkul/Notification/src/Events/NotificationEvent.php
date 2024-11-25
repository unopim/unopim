<?php

namespace Webkul\Notification\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements NotificationEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificationData;

    /**
     * Create a new event instance.
     *
     * @param array $notificationData
     * @return void
     */
    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }
}
