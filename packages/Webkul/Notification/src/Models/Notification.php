<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\Notification as NotificationContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Notification extends Model implements NotificationContract
{
    use BelongsToTenant;

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

    protected $appends = ['created_at_human'];

    /**
     * Get the created_at field in a human-readable format.
     */
    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the user notifications associated with the notification.
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }
}
