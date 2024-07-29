<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Notification\Contracts\Notification as NotificationContract;

class Notification extends Model implements NotificationContract
{
    protected $fillable = [
        'type',
        'read',
        'order_id',
    ];
}
