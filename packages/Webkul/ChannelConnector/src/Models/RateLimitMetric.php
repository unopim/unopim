<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class RateLimitMetric extends Model
{
    use BelongsToTenant;

    protected $table = 'rate_limit_metrics';

    protected $fillable = [
        'connector_id',
        'adapter_type',
        'endpoint',
        'requests_made',
        'limit_total',
        'limit_remaining',
        'reset_at',
        'recorded_at',
        'status',
        'response_time_ms',
    ];

    protected $casts = [
        'requests_made' => 'integer',
        'limit_total' => 'integer',
        'limit_remaining' => 'integer',
        'response_time_ms' => 'integer',
        'reset_at' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ChannelConnector::class, 'connector_id');
    }

    /**
     * Get percentage of rate limit consumed
     */
    public function getConsumedPercentageAttribute(): float
    {
        if ($this->limit_total === 0) {
            return 0;
        }

        return round((($this->limit_total - $this->limit_remaining) / $this->limit_total) * 100, 2);
    }

    /**
     * Check if rate limit is approaching threshold
     */
    public function isApproachingLimit(int $threshold = 80): bool
    {
        return $this->consumed_percentage >= $threshold;
    }

    /**
     * Check if rate limit is critical
     */
    public function isCritical(int $threshold = 90): bool
    {
        return $this->consumed_percentage >= $threshold;
    }
}
