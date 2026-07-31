<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\Repositories\AgentExecutionRepository;
use Webkul\AiAgent\Services\AgentService;

/**
 * Queued job for asynchronous agent execution.
 */
class ExecuteAgentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * The job timeout in seconds.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * @param  array<string, mixed>  $payloadData  Serialized AgentPayload
     */
    public function __construct(
        public readonly array $payloadData,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AgentService $agentService): void
    {
        $payload = AgentPayload::fromArray($this->payloadData);

        $agentService->execute($payload);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $payload = AgentPayload::fromArray($this->payloadData);
        $executionId = $payload->metadata['executionId'] ?? null;

        if ($executionId) {
            resolve(AgentExecutionRepository::class)
                ->markFailed($executionId, 'Job failed: '.$exception->getMessage());
        }
    }
}
