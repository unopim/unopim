<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;

class JobTrack extends Model implements JobTrackContract
{
    protected $table = 'job_track';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'state',
        'type',
        'action',
        'validation_strategy',
        'validation_strategy',
        'allowed_errors',
        'processed_rows_count',
        'invalid_rows_count',
        'errors_count',
        'errors',
        'field_separator',
        'file_path',
        'images_directory_path',
        'error_file_path',
        'summary',
        'started_at',
        'completed_at',
        'meta',
        'job_instances_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'summary'      => 'array',
        'meta'         => 'array',
        'errors'       => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the job that owns the job batch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function batches()
    {
        return $this->hasMany(JobTrackBatchProxy::modelClass());
    }

    /**
     * Get the job parent instance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jobInstance()
    {
        return $this->belongsTo(JobInstancesProxy::modelClass(), 'job_instances_id', 'id');
    }
}
