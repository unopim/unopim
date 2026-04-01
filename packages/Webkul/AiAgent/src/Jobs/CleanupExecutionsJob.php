<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\AiAgent\Repositories\AgentExecutionRepository;

/**
 * Queued job for cleaning up old execution records.
 */
class CleanupExecutionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $repository->model
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
}
