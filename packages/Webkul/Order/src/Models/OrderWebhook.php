<?php

namespace Webkul\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Order\Contracts\OrderWebhook as OrderWebhookContract;

class OrderWebhook extends Model implements OrderWebhookContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_webhooks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'channel_id',
        'channel_type',
        'event_type',
        'webhook_url',
        'secret_key',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
