<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\ChannelConnector\Contracts\ChannelSyncJob as ChannelSyncJobContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ChannelSyncJob extends Model implements ChannelSyncJobContract
{
    use BelongsToTenant;

    protected $table = 'channel_sync_jobs';

    protected $fillable = [
        'channel_connector_id',
        'job_id',
        'status',
        'sync_type',
        'total_products',
        'synced_products',
        'failed_products',
        'error_summary',
        'retry_of_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'error_summary'   => 'array',
        'total_products'  => 'integer',
        'synced_products' => 'integer',
        'failed_products' => 'integer',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ChannelConnector::class, 'channel_connector_id');
    }

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retry_of_id');
    }

    public function retries(): HasMany
    {
        return $this->hasMany(self::class, 'retry_of_id');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(ChannelSyncConflict::class, 'channel_sync_job_id');
    }
}
