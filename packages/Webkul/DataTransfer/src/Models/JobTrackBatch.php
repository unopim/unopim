<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class JobTrackBatch extends Model implements JobTrackBatchContract
{
    use BelongsToTenant;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'state',
        'data',
        'summary',
        'job_track_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'summary' => 'array',
        'data'    => 'array',
    ];

    /**
     * Get the jobTrack that owns the jobTrack batch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jobTrack()
    {
        return $this->belongsTo(JobTrackProxy::modelClass());
    }
}
