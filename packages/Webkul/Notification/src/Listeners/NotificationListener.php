<?php

namespace Webkul\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Notification\Events\NotificationEventInterface;
use Webkul\Notification\Models\Notification;
use Webkul\User\Models\Admin;

class NotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\NotificationEvent  $event
     * @return void
     */
    public function handle(NotificationEventInterface $event)
    {
        // Create the notification
        $notification = Notification::create([
            'type' => $event->notificationData['type'],
            'url' => $event->notificationData['url'] ?? null,
            'title' => $event->notificationData['title'] ?? null,
            'description' => $event->notificationData['description'] ?? null,
            'context' => $event->notificationData['context'] ?? null,
        ]);

        // Attach the notification to users
        $users = Admin::whereIn('id', $event->notificationData['user_ids'])->get();
        foreach ($users as $user) {
            $user->notifications()->attach($notification->id);
        }
    }
}
