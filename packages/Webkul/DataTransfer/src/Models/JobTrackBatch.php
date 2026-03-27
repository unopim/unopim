<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Database\Factories\JobTrackBatchFactory;

class JobTrackBatch extends Model implements JobTrackBatchContract
{
    use HasFactory;

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
     * @return BelongsTo
     */
    public function jobTrack()
    {
        return $this->belongsTo(JobTrackProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return JobTrackBatchFactory::new();
    }
}
