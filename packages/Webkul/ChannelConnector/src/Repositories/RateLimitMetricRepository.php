<?php

namespace Webkul\ChannelConnector\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Webkul\ChannelConnector\Models\RateLimitMetric;
use Webkul\Core\Eloquent\Repository;

class RateLimitMetricRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return RateLimitMetric::class;
    }

    /**
     * Record a rate limit metric
     */
    public function record(array $data): RateLimitMetric
    {
        $status = $this->calculateStatus(
            $data['limit_remaining'] ?? 0,
            $data['limit_total'] ?? 1
        );

        return $this->create(array_merge($data, [
            'status' => $status,
            'recorded_at' => now(),
        ]));
    }

    /**
     * Get latest metrics for a connector
     */
    public function getLatestForConnector(int $connectorId, int $limit = 10): Collection
    {
        return $this->model
            ->where('connector_id', $connectorId)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get metrics for time range
     */
    public function getForTimeRange(int $connectorId, Carbon $start, Carbon $end): Collection
    {
        return $this->model
            ->where('connector_id', $connectorId)
            ->whereBetween('recorded_at', [$start, $end])
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    /**
     * Get aggregated stats for connector
     */
    public function getAggregatedStats(int $connectorId, string $period = '24h'): array
    {
        $start = match ($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $metrics = $this->getForTimeRange($connectorId, $start, now());

        if ($metrics->isEmpty()) {
            return [
                'average_consumed' => 0,
                'peak_consumed' => 0,
                'total_requests' => 0,
                'average_response_time' => 0,
                'warning_count' => 0,
                'critical_count' => 0,
            ];
        }

        return [
            'average_consumed' => round($metrics->avg('consumed_percentage'), 2),
            'peak_consumed' => round($metrics->max('consumed_percentage'), 2),
            'total_requests' => $metrics->sum('requests_made'),
            'average_response_time' => round($metrics->avg('response_time_ms'), 0),
            'warning_count' => $metrics->where('status', 'warning')->count(),
            'critical_count' => $metrics->where('status', 'critical')->count(),
        ];
    }

    /**
     * Get all connectors with critical rate limits
     */
    public function getCriticalConnectors(): Collection
    {
        return $this->model
            ->where('status', 'critical')
            ->where('recorded_at', '>=', now()->subMinutes(5))
            ->with('connector')
            ->get()
            ->unique('connector_id');
    }

    /**
     * Calculate status based on consumption
     */
    protected function calculateStatus(int $remaining, int $total): string
    {
        if ($total === 0 || $remaining === 0) {
            return 'exceeded';
        }

        $consumed = (($total - $remaining) / $total) * 100;

        return match (true) {
            $consumed >= 90 => 'critical',
            $consumed >= 80 => 'warning',
            default => 'ok',
        };
    }
}
