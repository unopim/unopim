<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\ChannelConnector\Contracts\ChannelSyncConflict as ChannelSyncConflictContract;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\User\Models\Admin;

class ChannelSyncConflict extends Model implements ChannelSyncConflictContract
{
    use BelongsToTenant;

    protected $table = 'channel_sync_conflicts';

    protected $fillable = [
        'channel_connector_id',
        'channel_sync_job_id',
        'product_id',
        'conflict_type',
        'conflicting_fields',
        'pim_modified_at',
        'channel_modified_at',
        'resolution_status',
        'resolution_details',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'conflicting_fields'  => 'array',
        'resolution_details'  => 'array',
        'pim_modified_at'     => 'datetime',
        'channel_modified_at' => 'datetime',
        'resolved_at'         => 'datetime',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ChannelConnector::class, 'channel_connector_id');
    }

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(ChannelSyncJob::class, 'channel_sync_job_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }
}
