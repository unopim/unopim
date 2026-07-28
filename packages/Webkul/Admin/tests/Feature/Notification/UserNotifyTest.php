<?php

use Illuminate\Contracts\Mail\Factory;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Notification\Events\NotificationEvent;
use Webkul\Notification\Listeners\SendNotificationListener;
use Webkul\Notification\Mail\UserNotify;
use Webkul\User\Models\Admin;

it('dispatches NotificationEvent with correct parameters', function () {
    Event::fake();

    $exportData = new stdClass;
    $exportData->id = 123;
    $exportData->user = (object) [
        'id'    => 1,
        'email' => 'user@example.com',
    ];

    $notificationData = [
        'type'         => 'export',
        'route'        => 'admin.settings.data_transfer.tracker.view',
        'route_params' => ['batch_id' => $exportData->id],
        'title'        => 'Export Process Started',
        'description'  => 'The export process for your data has started successfully.',
        'user_ids'     => [$exportData->user->id],
        'mailable'     => true,
        'user_emails'  => [$exportData->user->email],
        'templateName' => 'admin::emails.data-transfer.index',
        'templateData' => [
            'export' => $exportData,
        ],
    ];

    NotificationEvent::dispatch($notificationData);

    Event::assertDispatched(NotificationEvent::class, function ($event) use ($notificationData) {
        return $event->notificationData === $notificationData;
    });
});

/**
 * UserNotify forwards its `templateData` straight into Content(with: ...), so the
 * keys a producer passes become the view's variables. These render each producer's
 * real shape through a queue serialization round trip — the path where the job was
 * failing, from a variable-name mismatch and a json_decode() applied to an
 * attribute Eloquent had already cast to an array.
 */
$dispatch = function (UserNotify $mailable): void {
    $job = unserialize(serialize(new SendQueuedMailable($mailable)));

    $job->handle(app(Factory::class));
};

it('renders and queues the data transfer notification without failing', function () use ($dispatch) {
    $jobTrack = JobTrack::factory()->export()->completed()->create([
        'meta' => ['type' => 'export', 'code' => 'product-export'],
    ]);

    expect($jobTrack->meta)->toBeArray();

    $mailable = new UserNotify(
        ['admin@example.com'],
        'Export completed',
        'admin::emails.data-transfer.index',
        ['templateData' => $jobTrack]
    );

    expect($mailable->render())
        ->toContain('Export')
        ->toContain('product-export')
        ->toContain('completed');

    $dispatch($mailable);
});

it('renders the data transfer notification when meta carries no type or code', function () use ($dispatch) {
    $jobTrack = JobTrack::factory()->create(['meta' => []]);

    $dispatch(new UserNotify(
        ['admin@example.com'],
        'Import state changed',
        'admin::emails.data-transfer.index',
        ['templateData' => $jobTrack]
    ));
})->throwsNoExceptions();

it('renders and queues the completeness notification for a whole catalogue run', function () use ($dispatch) {
    $mailable = new UserNotify(
        ['admin@example.com'],
        'Completeness calculated',
        'completeness::emails.completeness-completed',
        ['totalProducts' => 42, 'familyId' => null]
    );

    expect($mailable->render())->toContain('42');

    $dispatch($mailable);
});

it('renders and queues the completeness notification scoped to a family', function () use ($dispatch) {
    $family = AttributeFamily::factory()->create();

    $mailable = new UserNotify(
        ['admin@example.com'],
        'Completeness calculated',
        'completeness::emails.completeness-completed',
        ['totalProducts' => 7, 'familyId' => $family->id]
    );

    expect($mailable->render())
        ->toContain('7')
        ->toContain($family->code);

    $dispatch($mailable);
});

it('rejects a non-array template payload at construction rather than inside the worker', function () {
    new UserNotify(['admin@example.com'], 'Subject', 'admin::emails.data-transfer.index', 'not-an-array');
})->throws(TypeError::class);

it('resolves the data transfer meta type and code when building the notification payload', function () {
    Event::fake([NotificationEvent::class]);

    $admin = Admin::factory()->create();

    $jobTrack = JobTrack::factory()->export()->completed()->create([
        'meta'    => ['type' => 'export', 'code' => 'product-export'],
        'user_id' => $admin->id,
    ]);

    app(SendNotificationListener::class)->sendNotification($jobTrack);

    Event::assertDispatched(NotificationEvent::class, function ($event) use ($jobTrack) {
        return $event->notificationData['type'] === 'export'
            && $event->notificationData['title'] === sprintf('Export #%d', $jobTrack->id)
            && $event->notificationData['description'] === sprintf('Export "product-export" %s', $jobTrack->state);
    });
});
