<?php

namespace Webkul\AiAgent\Repositories;

use Webkul\AiAgent\Models\AgentExecution;
use Webkul\Core\Eloquent\Repository;

class AgentExecutionRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return AgentExecution::class;
    }

    /**
     * Log a new execution record.
     *
     * @param  array<string, mixed>  $data
     */
    public function logExecution(array $data): AgentExecution
    {
        return $this->create($data);
    }

    /**
     * Mark an execution as completed.
     */
    public function markCompleted(int $id, string $output, int $tokensUsed, int $executionTimeMs): void
    {
        $this->update([
            'status'          => 'completed',
            'output'          => $output,
            'tokensUsed'      => $tokensUsed,
            'executionTimeMs' => $executionTimeMs,
        ], $id);
    }

    /**
     * Mark an execution as failed.
     */
    public function markFailed(int $id, string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error'  => $error,
        ], $id);
    }
}
