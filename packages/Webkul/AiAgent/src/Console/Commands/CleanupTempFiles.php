<?php

namespace Webkul\AiAgent\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Clean up temporary AI-generated files.
 *
 * Run via: php artisan ai-agent:cleanup
 * Recommended: schedule daily in the kernel.
 */
class CleanupTempFiles extends Command
{
    protected $signature = 'ai-agent:cleanup
                            {--days=7 : Delete files older than this many days}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Clean up temporary AI agent files (compressed images, exports, generated images)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days)->timestamp;
        $deleted = 0;

        // 1. Compressed images in system temp dir
        $tempDir = sys_get_temp_dir();
        $tempFiles = glob($tempDir.'/ai_compressed_*');

        foreach ($tempFiles as $file) {
            if (filemtime($file) < $cutoff) {
                if ($dryRun) {
                    $this->line("Would delete: {$file}");
                } else {
                    @unlink($file);
                }
                $deleted++;
            }
        }

        // 2. Storage directories for AI agent files
        $storageDirs = [
            storage_path('app/public/ai-agent/images'),
            storage_path('app/public/ai-agent/files'),
            storage_path('app/public/ai-agent/exports'),
            storage_path('app/public/ai-agent/generated'),
            storage_path('app/public/ai-agent/edited'),
        ];

        foreach ($storageDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $files = File::files($dir);

            foreach ($files as $file) {
                if ($file->getMTime() < $cutoff) {
                    if ($dryRun) {
                        $this->line("Would delete: {$file->getPathname()}");
                    } else {
                        @unlink($file->getPathname());
                    }
                    $deleted++;
                }
            }
        }

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$deleted} file(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
