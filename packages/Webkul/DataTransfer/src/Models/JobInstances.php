<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DataTransfer\Contracts\JobInstances as JobInstancesContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class JobInstances extends Model implements HistoryContract, JobInstancesContract
{
    use HistoryTrait;

    protected $table = 'job_instances';

    /** Tags for History */
    protected $historyTags = ['job_instance'];

    protected $casts = [
        'filters' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'entity_type',
        'type',
        'action',
        'validation_strategy',
        'allowed_errors',
        'field_separator',
        'file_path',
        'images_directory_path',
        'filters',
    ];

    /**
     * Get the options.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(JobTrackProxy::modelClass(), 'id');
    }
}
