<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\Services\AgentService;

/**
 * Batch job for processing multiple agent executions.
 * Useful for bulk product enrichment, mass description generation, etc.
 */
class BatchAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    /**
     * @param  array<int, array<string, mixed>>  $payloads  Array of serialized AgentPayload data
     */
    public function __construct(
        protected array $payloads,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AgentService $agentService): void
    {
        foreach ($this->payloads as $payloadData) {
            try {
                $payload = AgentPayload::fromArray($payloadData);
                $agentService->execute($payload);
            } catch (\Throwable $e) {
                Log::error('BatchAgentJob item failed', [
                    'agentId' => $payloadData['agentId'] ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
