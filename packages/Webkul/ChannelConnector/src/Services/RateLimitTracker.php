<?php

namespace Webkul\ChannelConnector\Services;

use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Repositories\RateLimitMetricRepository;

class RateLimitTracker
{
    public function __construct(
        protected RateLimitMetricRepository $rateLimitRepository
    ) {}

    /**
     * Record rate limit metrics from HTTP response headers
     */
    public function recordFromHeaders(
        int $connectorId,
        string $adapterType,
        array $headers,
        string $endpoint = null,
        int $responseTimeMs = null
    ): void {
        try {
            // Common rate limit header patterns
            $limitTotal = $this->extractHeaderValue($headers, [
                'X-RateLimit-Limit',
                'RateLimit-Limit',
                'X-Rate-Limit-Limit',
            ]);

            $limitRemaining = $this->extractHeaderValue($headers, [
                'X-RateLimit-Remaining',
                'RateLimit-Remaining',
                'X-Rate-Limit-Remaining',
            ]);

            $resetAt = $this->extractResetTime($headers);

            // Only record if we have meaningful data
            if ($limitTotal || $limitRemaining) {
                $this->rateLimitRepository->record([
                    'connector_id' => $connectorId,
                    'adapter_type' => $adapterType,
                    'endpoint' => $endpoint,
                    'requests_made' => $limitTotal && $limitRemaining
                        ? ($limitTotal - $limitRemaining)
                        : 0,
                    'limit_total' => $limitTotal ?? 0,
                    'limit_remaining' => $limitRemaining ?? 0,
                    'reset_at' => $resetAt,
                    'response_time_ms' => $responseTimeMs,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('[RateLimitTracker] Failed to record metrics', [
                'error' => $e->getMessage(),
                'connector_id' => $connectorId,
            ]);
        }
    }

    /**
     * Record explicit rate limit data
     */
    public function recordExplicit(array $data): void
    {
        try {
            $this->rateLimitRepository->record($data);
        } catch (\Exception $e) {
            Log::warning('[RateLimitTracker] Failed to record explicit metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract header value from multiple possible header names
     */
    protected function extractHeaderValue(array $headers, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            if (isset($headers[$name])) {
                $value = is_array($headers[$name]) ? $headers[$name][0] : $headers[$name];

                return (int) $value;
            }
        }

        return null;
    }

    /**
     * Extract reset timestamp from headers
     */
    protected function extractResetTime(array $headers): ?\Carbon\Carbon
    {
        $resetHeaders = [
            'X-RateLimit-Reset',
            'RateLimit-Reset',
            'X-Rate-Limit-Reset',
            'Retry-After',
        ];

        foreach ($resetHeaders as $header) {
            if (isset($headers[$header])) {
                $value = is_array($headers[$header]) ? $headers[$header][0] : $headers[$header];

                // Unix timestamp
                if (is_numeric($value)) {
                    return \Carbon\Carbon::createFromTimestamp((int) $value);
                }

                // ISO date string
                try {
                    return \Carbon\Carbon::parse($value);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }
}
