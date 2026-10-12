<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DataTransfer\Contracts\JobInstances as JobInstancesContract;
use Webkul\DataTransfer\Database\Factories\JobInstanceFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

#[Fillable([
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
])]
#[Table(name: 'job_instances')]
class JobInstances extends Model implements HistoryContract, JobInstancesContract
{
    use HasFactory,HistoryTrait;

    /** Tags for History */
    protected $historyTags = ['job_instance'];

    /**
     * Get the options.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(JobTrackProxy::modelClass(), 'id');
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return JobInstanceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }
}
