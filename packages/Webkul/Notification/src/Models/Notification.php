<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\Notification as NotificationContract;

class Notification extends Model implements NotificationContract
{
    protected $fillable = [
        'type',
        'route',
        'route_params',
        'title',
        'description',
        'context',
    ];

    protected $casts = [
        'context'      => 'array',
        'route_params' => 'array',
    ];

    /**
     * Get the user notifications associated with the notification.
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }
}
