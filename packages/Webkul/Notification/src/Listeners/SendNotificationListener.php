<?php

namespace Webkul\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Webkul\Notification\Events\NotificationEvent;
use Webkul\Notification\Mail\MailConfiguration;
use Webkul\User\Repositories\AdminRepository;

class SendNotificationListener implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AdminRepository $adminRepository,
        protected MailConfiguration $mailConfiguration,
    ) {}

    /**
     * Handle the event.
     */
    public function sendNotification($event): void
    {
        if (! config('notifications.enabled', true)) {
            Log::info('Notifications are disabled. No notification sent.', ['event' => $event]);

            return;
        }

        $mailConfigured = $this->mailConfiguration->isConfigured();

        $metaData = (object) array_merge(
            ['type' => '', 'code' => ''],
            (array) ($event->meta ?? [])
        );
        $admin = $this->adminRepository->find($event->user_id);

        event(new NotificationEvent([
            'type'         => $metaData->type,
            'route'        => 'admin.settings.data_transfer.tracker.view',
            'route_params' => ['batch_id' => $event->id],
            'title'        => sprintf('%s #%d', ucfirst((string) $metaData->type), $event->id),
            'description'  => sprintf('%s "%s" %s', ucfirst((string) $metaData->type), $metaData->code, $event->state),
            'user_ids'     => [$event->user_id],
            'mailable'     => $mailConfigured,
            'user_emails'  => [$admin['email']],
            'templateName' => 'admin::emails.data-transfer.index',
            'templateData' => [
                'templateData' => $event,
            ],
        ]));
    }
}
