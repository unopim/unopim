<?php

namespace Webkul\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Notification\Events\CreateNotification;
use Webkul\Notification\Events\NotificationEventInterface;
use Webkul\Notification\Models\Notification;

class NotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(NotificationEventInterface $event)
    {
        $notification = Notification::create([
            'type'         => $event->notificationData['type'],
            'route'        => $event->notificationData['route'] ?? null,
            'route_params' => $event->notificationData['route_params'] ?? null,
            'title'        => $event->notificationData['title'] ?? null,
            'description'  => $event->notificationData['description'] ?? null,
            'context'      => $event->notificationData['context'] ?? null,
        ]);

        $userNotificationsData = collect($event->notificationData['user_ids'])->map(function ($userId) {
            return [
                'admin_id' => $userId,
            ];
        })->toArray();

        $notification->userNotifications()->createMany($userNotificationsData);

        event(new CreateNotification);
    }
}
