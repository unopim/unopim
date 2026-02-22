<?php

namespace Webkul\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Webkul\Order\Events\SyncFailed;
use Webkul\User\Models\Admin;

/**
 * Notify Admin On Sync Failure Listener
 *
 * Sends notifications to administrators when order sync fails.
 * Runs asynchronously in the queue.
 */
class NotifyAdminOnSyncFailure implements ShouldQueue
{
    /**
     * The name of the queue connection to use.
     *
     * @var string
     */
    public $connection = 'database';

    /**
     * The name of the queue to use.
     *
     * @var string
     */
    public $queue = 'notifications';

    /**
     * Handle the event.
     *
     * @param  SyncFailed  $event
     * @return void
     */
    public function handle(SyncFailed $event): void
    {
        $channel = $event->channel;
        $syncLog = $event->syncLog;
        $exception = $event->exception;

        // Log the failure
        Log::error('Order sync failed for channel: ' . $channel->name, [
            'channel_id' => $channel->id,
            'sync_log_id' => $syncLog->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Get admins with order sync notification permission
        $admins = Admin::whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($q) {
                $q->where('name', 'orders.sync.notifications');
            });
        })->get();

        // If no specific admins, notify super admins
        if ($admins->isEmpty()) {
            $admins = Admin::whereHas('roles', function ($query) {
                $query->where('name', 'Administrator');
            })->get();
        }

        // Send notifications (would use a notification class in production)
        foreach ($admins as $admin) {
            // Example: Notification::send($admin, new SyncFailedNotification($event));
            // For now, just log
            Log::info('Would notify admin: ' . $admin->email, [
                'channel' => $channel->name,
                'error' => substr($exception->getMessage(), 0, 100),
            ]);
        }
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param  SyncFailed  $event
     * @return bool
     */
    public function shouldQueue(SyncFailed $event): bool
    {
        return true;
    }
}
