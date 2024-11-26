<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\UserNotification as UserNotificationContract;

class UserNotification extends Model implements UserNotificationContract
{
    protected $fillable = [
        'admin_id',
        'notification_id',
        'read',
    ];

    /**
     * Relationship with Notification.
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
