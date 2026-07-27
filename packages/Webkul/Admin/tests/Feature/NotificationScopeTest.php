<?php

use Webkul\Notification\Models\Notification;
use Webkul\Notification\Models\UserNotification;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;

/*
 * The read-and-redirect endpoint must only honour a notification for an admin who
 * is actually a recipient, so notification ids cannot be enumerated to redirect
 * to another admin's notification target.
 */
it('returns 404 for a notification the admin does not receive', function () {
    $recipient = Admin::factory()->create();
    $other = Admin::factory()->create();

    $notification = Notification::create(['type' => 'info']);
    UserNotification::create(['admin_id' => $recipient->id, 'notification_id' => $notification->id, 'read' => 0]);

    $this->actingAs($other, 'admin');

    get(route('admin.notification.viewed_notification', ['id' => $notification->id]))
        ->assertStatus(404);
});

it('redirects for a notification the admin receives', function () {
    $recipient = Admin::factory()->create();

    $notification = Notification::create(['type' => 'info']);
    UserNotification::create(['admin_id' => $recipient->id, 'notification_id' => $notification->id, 'read' => 0]);

    $this->actingAs($recipient, 'admin');

    get(route('admin.notification.viewed_notification', ['id' => $notification->id]))
        ->assertStatus(302);

    expect(UserNotification::where('notification_id', $notification->id)->where('admin_id', $recipient->id)->value('read'))->toBe(1);
});
