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
        $path = storage_path("logs/{$folderName}/{$jobId}/{$fileName}.log");

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
        return "logs/job-tracker/{$id}/job.log";
    }
}
