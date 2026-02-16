<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Repositories\RateLimitMetricRepository;

class RateLimitController extends Controller
{
    public function __construct(
        protected RateLimitMetricRepository $rateLimitRepository,
        protected ChannelConnectorRepository $connectorRepository
    ) {}

    /**
     * Get rate limit metrics for a connector
     */
    public function show(int $connectorId): JsonResponse
    {
        $connector = $this->connectorRepository->find($connectorId);

        if (! $connector) {
            return new JsonResponse(['error' => 'Connector not found'], 404);
        }

        $latest = $this->rateLimitRepository->getLatestForConnector($connectorId, 1)->first();
        $stats24h = $this->rateLimitRepository->getAggregatedStats($connectorId, '24h');
        $stats7d = $this->rateLimitRepository->getAggregatedStats($connectorId, '7d');

        return new JsonResponse([
            'connector' => [
                'id' => $connector->id,
                'name' => $connector->name,
                'channel_type' => $connector->channel_type,
            ],
            'current' => $latest ? [
                'consumed_percentage' => $latest->consumed_percentage,
                'limit_remaining' => $latest->limit_remaining,
                'limit_total' => $latest->limit_total,
                'reset_at' => $latest->reset_at?->toIso8601String(),
                'status' => $latest->status,
            ] : null,
            'stats_24h' => $stats24h,
            'stats_7d' => $stats7d,
        ]);
    }

    /**
     * Get historical data for charts
     */
    public function history(int $connectorId, string $period = '24h'): JsonResponse
    {
        $start = match ($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $metrics = $this->rateLimitRepository->getForTimeRange($connectorId, $start, now());

        $chartData = $metrics->map(fn ($metric) => [
            'timestamp' => $metric->recorded_at->toIso8601String(),
            'consumed_percentage' => $metric->consumed_percentage,
            'requests_made' => $metric->requests_made,
            'response_time_ms' => $metric->response_time_ms,
            'status' => $metric->status,
        ])->values();

        return new JsonResponse([
            'period' => $period,
            'data' => $chartData,
        ]);
    }

    /**
     * Get all connectors with their current rate limit status
     */
    public function index(): JsonResponse
    {
        $connectors = $this->connectorRepository->all();

        $data = $connectors->map(function ($connector) {
            $latest = $this->rateLimitRepository->getLatestForConnector($connector->id, 1)->first();

            return [
                'id' => $connector->id,
                'name' => $connector->name,
                'channel_type' => $connector->channel_type,
                'status' => $latest?->status ?? 'unknown',
                'consumed_percentage' => $latest?->consumed_percentage ?? 0,
                'last_checked' => $latest?->recorded_at?->diffForHumans(),
            ];
        });

        return new JsonResponse(['connectors' => $data]);
    }

    /**
     * Get critical alerts
     */
    public function alerts(): JsonResponse
    {
        $critical = $this->rateLimitRepository->getCriticalConnectors();

        $alerts = $critical->map(fn ($metric) => [
            'connector_id' => $metric->connector_id,
            'connector_name' => $metric->connector->name,
            'channel_type' => $metric->connector->channel_type,
            'consumed_percentage' => $metric->consumed_percentage,
            'limit_remaining' => $metric->limit_remaining,
            'reset_at' => $metric->reset_at?->toIso8601String(),
            'recorded_at' => $metric->recorded_at->toIso8601String(),
        ]);

        return new JsonResponse([
            'count' => $alerts->count(),
            'alerts' => $alerts,
        ]);
    }
}
