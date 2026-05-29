<?php

declare(strict_types=1);

namespace Webkul\Notification\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel
    {
        return new Channel('notification');
    }

    /**
     * Separate queue.
     *
     * Command: `php artisan queue:work --queue=broadcastable`
     */
    public function broadcastQueue(): string
    {
        return 'broadcastable';
    }

    /**
     * Get the channels the event should broadcast as.
     */
    public function broadcastAs(): string
    {
        return 'create-notification';
    }
}
