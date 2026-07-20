<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\AiAgent\Repositories\AgentExecutionRepository;

/**
 * Queued job for cleaning up old execution records.
 */
class CleanupExecutionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $retentionDays  Number of days to retain execution logs
     */
    public function __construct(
        public readonly int $retentionDays = 30,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AgentExecutionRepository $repository): void
    {
        $cutoff = now()->subDays($this->retentionDays);

        $repository->getModel()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
}
