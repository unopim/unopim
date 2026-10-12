<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\Notification as NotificationContract;

#[Appends(['created_at_human'])]
#[Fillable([
    'type',
    'route',
    'route_params',
    'title',
    'description',
    'context',
])]
class Notification extends Model implements NotificationContract
{
    /**
     * Get the created_at field in a human-readable format.
     */
    protected function createdAtHuman(): Attribute
    {
        return Attribute::make(get: fn () => $this->created_at->diffForHumans());
    }

    /**
     * Get the user notifications associated with the notification.
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    protected function casts(): array
    {
        return [
            'context'      => 'array',
            'route_params' => 'array',
        ];
    }
}
