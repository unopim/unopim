<?php

namespace Webkul\AiAgent\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Webkul\AiAgent\Repositories\ChatReplyRepository;

/**
 * Clean up temporary AI-generated files.
 *
 * Run via: php artisan ai-agent:cleanup
 * Recommended: schedule daily in the kernel.
 */
#[Description('Clean up temporary AI agent files (compressed images, exports, generated images) and old queued chat replies')]
#[Signature('ai-agent:cleanup
                            {--days=7 : Delete files older than this many days}
                            {--dry-run : Show what would be deleted without deleting}')]
class CleanupTempFiles extends Command
{
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

            $files = File::allFiles($dir);

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

        $replies = $this->pruneChatReplies(now()->subDays($days), $dryRun);
        $this->info("{$action} {$replies} queued chat reply row(s) older than {$days} days.");

        return self::SUCCESS;
    }

    /**
     * Delete (or, on a dry run, count) queued chat replies created before the cutoff.
     *
     * The rows only carry a reply to the widget while it is generated, so they
     * are safe to drop once the conversation moved on.
     */
    protected function pruneChatReplies(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $chatReplyRepository = app(ChatReplyRepository::class);

        return $dryRun
            ? $chatReplyRepository->countCreatedBefore($cutoff)
            : $chatReplyRepository->pruneCreatedBefore($cutoff);
    }
}
