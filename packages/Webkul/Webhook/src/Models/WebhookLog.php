<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'webhook_id',
    'sku',
    'event',
    'user',
    'status',
    'http_code',
    'extra',
])]
#[Table(name: 'webhook_logs')]
class WebhookLog extends Model
{
    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }
}
