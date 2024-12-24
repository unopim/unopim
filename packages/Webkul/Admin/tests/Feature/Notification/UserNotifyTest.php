<?php

use Illuminate\Support\Facades\Event;
use Webkul\Notification\Events\NotificationEvent;

it('dispatches NotificationEvent with correct parameters', function () {
    // Fake events
    Event::fake();

    // Mock the export data
    $exportData = new stdClass;
    $exportData->id = 123;
    $exportData->user = (object) [
        'id'    => 1,
        'email' => 'user@example.com',
    ];

    // Prepare notification data
    $notificationData = [
        'type'         => 'export',
        'route'        => 'admin.settings.data_transfer.tracker.view',
        'route_params' => ['batch_id' => $exportData->id],
        'title'        => 'Export Process Started',
        'description'  => 'The export process for your data has started successfully.',
        'user_ids'     => [$exportData->user->id],
        'mailable'     => true,
        'user_emails'  => [$exportData->user->email],
        'templateName' => 'admin::emails.export',
        'templateData' => [
            'export' => $exportData,
        ],
    ];

    // Dispatch the NotificationEvent
    NotificationEvent::dispatch($notificationData);

    // Assert the event was dispatched with the correct data
    Event::assertDispatched(NotificationEvent::class, function ($event) use ($notificationData) {
        return $event->notificationData === $notificationData;
    });
});
