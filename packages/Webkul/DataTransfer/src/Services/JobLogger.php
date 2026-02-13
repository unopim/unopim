<?php

namespace Webkul\DataTransfer\Services;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class JobLogger
{
    /**
     * Creates a new logger for job-tracker
     */
    public static function make(int|string $jobId, string $level = 'info', string $folderName = 'job-tracker', string $fileName = 'job'): LoggerInterface
    {
        $tenantPrefix = static::tenantPrefix();
        $path = storage_path("logs/{$tenantPrefix}{$folderName}/{$jobId}/{$fileName}.log");

        $logger = Log::build([
            'driver' => 'single',
            'path'   => $path,
            'level'  => $level,
        ]);

        return $logger;
    }

    /**
     * returns default generated log file path
     */
    public static function getJobLogPath(string|int $id): string
    {
        $tenantPrefix = static::tenantPrefix();

        return "logs/{$tenantPrefix}job-tracker/{$id}/job.log";
    }

    /**
     * Get tenant-specific log folder prefix.
     */
    private static function tenantPrefix(): string
    {
        $tenantId = core()->getCurrentTenantId();

        return $tenantId ? "tenant-{$tenantId}/" : '';
    }
}
