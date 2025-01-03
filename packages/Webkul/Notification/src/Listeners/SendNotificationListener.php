<?php

namespace Webkul\Notification\Listeners;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Webkul\Notification\Events\NotificationEvent;
use Webkul\User\Repositories\AdminRepository;

class SendNotificationListener
{
    public function __construct(public AdminRepository $adminRepository) {}

    /**
     * Handle the event.
     *
     * @return void
     */
    public function sendNotification($event)
    {
        if (! env('NOTIFICATIONS_ENABLED', true)) {
            Log::info('Notifications are disabled. No notification sent.', ['event' => $event]);

            return;
        }

        $mailConfigured = Config::get('mail.default') &&
            Config::get('mail.mailers.smtp.host') &&
            Config::get('mail.mailers.smtp.port') &&
            Config::get('mail.mailers.smtp.username') &&
            Config::get('mail.mailers.smtp.password');

        $metaData = json_decode($event->meta);
        //@TODO: manage user details with relation to the event
        $admin = $this->adminRepository->find($event->user_id);

        NotificationEvent::dispatch([
            'type'         => $metaData->type,
            'route'        => 'admin.settings.data_transfer.tracker.view',
            'route_params' => ['batch_id' => $event->id],
            'title'        => sprintf('%s #%d', ucfirst($metaData->type), $event->id),
            'description'  => sprintf('%s "%s" %s', ucfirst($metaData->type), $metaData->code, $event->state),
            'user_ids'     => [$event->user_id],
            'mailable'     => $mailConfigured,
            'user_emails'  => [$admin['email']],
            'templateName' => 'admin::emails.data-transfer.index',
            'templateData' => [
                'templateData' => $event,
            ],
        ]);
    }
}
