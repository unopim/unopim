<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'sku',
    'user',
    'status',
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
