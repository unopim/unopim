<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Database\Factories\JobTrackFactory;

#[Fillable([
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
])]
#[Table(name: 'job_track')]
class JobTrack extends Model implements JobTrackContract
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the job that owns the job batch.
     *
     * @return HasMany
     */
    public function batches()
    {
        return $this->hasMany(JobTrackBatchProxy::modelClass());
    }

    /**
     * Get the job parent instance.
     *
     * @return BelongsTo
     */
    public function jobInstance()
    {
        return $this->belongsTo(JobInstancesProxy::modelClass(), 'job_instances_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return JobTrackFactory::new();
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'summary'      => 'array',
            'meta'         => 'array',
            'errors'       => 'array',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
