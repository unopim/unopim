<?php

namespace Webkul\Pricing\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Pricing\Events\MarginBlocked;

class NotifyMarginViolation
{
    /**
     * Handle a margin blocked event by creating a notification or logging it.
     */
    public function handle(MarginBlocked $event): void
    {
        $marginEvent = $event->marginEvent;

        $message = sprintf(
            'Margin violation: Product #%d proposed price %s %s is below break-even %s %s (margin: %s%%, minimum: %s%%)',
            $marginEvent->product_id,
            $marginEvent->currency_code,
            number_format($marginEvent->proposed_price, 4),
            $marginEvent->currency_code,
            number_format($marginEvent->break_even_price, 4),
            number_format($marginEvent->margin_percentage, 2),
            number_format($marginEvent->minimum_margin_percentage, 2)
        );

        // Attempt to use Webkul\Notification if available, otherwise fall back to logging
        if (class_exists(\Webkul\Notification\Repositories\NotificationRepository::class)) {
            try {
                $notificationRepository = app(\Webkul\Notification\Repositories\NotificationRepository::class);

                $notificationRepository->create([
                    'type'    => 'margin_violation',
                    'message' => $message,
                    'data'    => json_encode([
                        'product_id'      => $marginEvent->product_id,
                        'channel_id'      => $marginEvent->channel_id,
                        'proposed_price'   => $marginEvent->proposed_price,
                        'break_even_price' => $marginEvent->break_even_price,
                        'margin_event_id'  => $marginEvent->id,
                    ]),
                ]);

                return;
            } catch (\Throwable $e) {
                Log::warning('Failed to create notification for margin violation, falling back to log.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::warning($message, [
            'margin_event_id' => $marginEvent->id,
            'product_id'      => $marginEvent->product_id,
            'channel_id'      => $marginEvent->channel_id,
            'event_type'      => $marginEvent->event_type,
        ]);
    }
}
