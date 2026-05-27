<?php

use Webkul\Notification\Models\Notification;
use Webkul\Notification\Models\UserNotification;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the notification index page', function () {
    $this->get(route('admin.notification.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.notifications.title'));
});

it('should return notifications as json for ajax request', function () {
    $notification = Notification::create([
        'type'        => 'export',
        'title'       => 'Test Notification',
        'description' => 'Test notification description',
    ]);

    UserNotification::create([
        'admin_id'        => auth()->id(),
        'notification_id' => $notification->id,
        'read'            => 0,
    ]);

    $response = $this->getJson(route('admin.notification.get_notification'));

    $response->assertOk();

    $data = $response->json();

    $this->assertArrayHasKey('search_results', $data);
    $this->assertArrayHasKey('total_unread', $data);
    $this->assertGreaterThanOrEqual(1, $data['total_unread']);
});

it('should return unread notifications when filtered by read status', function () {
    $notification = Notification::create([
        'type'        => 'export',
        'title'       => 'Unread Notification',
        'description' => 'This is unread',
    ]);

    UserNotification::create([
        'admin_id'        => auth()->id(),
        'notification_id' => $notification->id,
        'read'            => 0,
    ]);

    $readNotification = Notification::create([
        'type'        => 'export',
        'title'       => 'Read Notification',
        'description' => 'This is read',
    ]);

    UserNotification::create([
        'admin_id'        => auth()->id(),
        'notification_id' => $readNotification->id,
        'read'            => 1,
    ]);

    $response = $this->getJson(route('admin.notification.get_notification', ['read' => 0, 'limit' => 10]));

    $response->assertOk();

    $data = $response->json();

    $records = collect($data['search_results']['data']);

    $this->assertTrue($records->every(fn ($n) => $n['read'] === 0));
});

it('should mark a notification as read and redirect', function () {
    $notification = Notification::create([
        'type'        => 'export',
        'title'       => 'Clickable Notification',
        'description' => 'Click to mark as read',
    ]);

    UserNotification::create([
        'admin_id'        => auth()->id(),
        'notification_id' => $notification->id,
        'read'            => 0,
    ]);

    $this->get(route('admin.notification.viewed_notification', $notification->id))
        ->assertRedirect();

    $this->assertDatabaseHas('user_notifications', [
        'admin_id'        => auth()->id(),
        'notification_id' => $notification->id,
        'read'            => 1,
    ]);
});

it('should mark a notification as read and redirect to its route', function () {
    $notification = Notification::create([
        'type'         => 'export',
        'title'        => 'Export Completed',
        'description'  => 'Your export is ready',
        'route'        => 'admin.notification.index',
        'route_params' => [],
    ]);

    UserNotification::create([
        'admin_id'        => auth()->id(),
        'notification_id' => $notification->id,
        'read'            => 0,
    ]);

    $response = $this->get(route('admin.notification.viewed_notification', $notification->id));

    $response->assertRedirect(route('admin.notification.index'));

    $this->assertDatabaseHas('user_notifications', [
        'notification_id' => $notification->id,
        'read'            => 1,
    ]);
});

it('should return 404 for non-existent notification', function () {
    $this->get(route('admin.notification.viewed_notification', 99999))
        ->assertNotFound();
});

it('should mark all notifications as read', function () {
    foreach (range(1, 3) as $i) {
        $notification = Notification::create([
            'type'        => 'export',
            'title'       => "Notification $i",
            'description' => "Description $i",
        ]);

        UserNotification::create([
            'admin_id'        => auth()->id(),
            'notification_id' => $notification->id,
            'read'            => 0,
        ]);
    }

    $response = $this->postJson(route('admin.notification.read_all'));

    $response->assertOk();
    $response->assertJsonFragment([
        'success_message' => trans('admin::app.notifications.marked-success'),
    ]);

    $unreadCount = UserNotification::where('admin_id', auth()->id())
        ->where('read', 0)
        ->count();

    $this->assertEquals(0, $unreadCount);
});

it('should return total unread count in response', function () {
    foreach (range(1, 5) as $i) {
        $notification = Notification::create([
            'type'        => 'export',
            'title'       => "Notification $i",
            'description' => "Description $i",
        ]);

        UserNotification::create([
            'admin_id'        => auth()->id(),
            'notification_id' => $notification->id,
            'read'            => $i <= 3 ? 0 : 1,
        ]);
    }

    $response = $this->getJson(route('admin.notification.get_notification'));

    $data = $response->json();

    $this->assertGreaterThanOrEqual(3, $data['total_unread']);
});
