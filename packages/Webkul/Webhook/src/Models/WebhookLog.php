<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class WebhookLog extends Model
{
    use BelongsToTenant;

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
