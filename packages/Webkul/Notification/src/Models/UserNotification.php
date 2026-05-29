<?php

declare(strict_types=1);

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Notification\Contracts\UserNotification as UserNotificationContract;

class UserNotification extends Model implements UserNotificationContract
{
    protected $fillable = [
        'admin_id',
        'notification_id',
        'read',
    ];

    protected $casts = [
        'read' => 'integer',
    ];

    /**
     * Relationship with Notification.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
