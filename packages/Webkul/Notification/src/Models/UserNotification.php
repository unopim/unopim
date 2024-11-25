<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\UserNotification as UserNotificationContract;

class UserNotification extends Model implements UserNotificationContract
{
    protected $fillable = [
        'user_id',
        'notification_id',
        'read',
    ];
}
