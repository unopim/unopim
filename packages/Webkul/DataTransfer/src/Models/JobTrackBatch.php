<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Database\Factories\JobTrackBatchFactory;

#[Fillable([
    'state',
    'data',
    'summary',
    'job_track_id',
])]
#[WithoutTimestamps]
class JobTrackBatch extends Model implements JobTrackBatchContract
{
    use HasFactory;

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

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'data'    => 'array',
        ];
    }
}
