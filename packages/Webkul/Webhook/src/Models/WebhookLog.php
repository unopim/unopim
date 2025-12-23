<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'sku',
        'user',
        'status',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];
}
