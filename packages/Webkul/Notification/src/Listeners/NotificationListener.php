<?php

namespace Webkul\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Webkul\Notification\Events\CreateNotification;
use Webkul\Notification\Events\NotificationEventInterface;
use Webkul\Notification\Mail\UserNotify;
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

        $userNotificationsData = array_map(
            fn ($userId) => [
                'admin_id' => $userId,
            ],
            $event->notificationData['user_ids']
        );

        $notification->userNotifications()->createMany($userNotificationsData);

        event(new CreateNotification);

        if (isset($event->notificationData['mailable']) && $event->notificationData['mailable']) {
            Mail::queue(new UserNotify(
                $event->notificationData['user_emails'],
                $event->notificationData['subject'] ?? $event->notificationData['title'],
                $event->notificationData['templateName'],
                $event->notificationData['templateData']
            ));
        }
    }
}
