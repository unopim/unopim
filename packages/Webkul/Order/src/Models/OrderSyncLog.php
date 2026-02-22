<?php

namespace Webkul\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Order\Contracts\OrderSyncLog as OrderSyncLogContract;

class OrderSyncLog extends Model implements OrderSyncLogContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'channel_type',
        'channel_order_id',
        'status',
        'error_message',
        'sync_data',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sync_data' => 'array',
        'synced_at' => 'datetime',
    ];
}
