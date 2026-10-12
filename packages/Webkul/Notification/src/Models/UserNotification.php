<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\UserNotification as UserNotificationContract;

#[Fillable([
    'admin_id',
    'notification_id',
    'read',
])]
class UserNotification extends Model implements UserNotificationContract
{
    /**
     * Relationship with Notification.
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    protected function casts(): array
    {
        return [
            'read' => 'integer',
        ];
    }
}
