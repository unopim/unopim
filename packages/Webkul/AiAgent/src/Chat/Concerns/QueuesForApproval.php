<?php

namespace Webkul\AiAgent\Chat\Concerns;

use Illuminate\Support\Facades\DB;
use Webkul\AiAgent\Chat\ChatContext;

/**
 * Enables write tools to queue changes for human review
 * when approval_mode is set to 'review' in configuration.
 *
 * Tools using this trait should call shouldQueueForApproval()
 * before executing writes. If true, call queueChange() instead.
 */
trait QueuesForApproval
{
    /**
     * Check if the current operation should be queued for approval.
     */
    protected function shouldQueueForApproval(): bool
    {
        $mode = core()->getConfigData('general.magic_ai.agentic_pim.approval_mode');

        return $mode === 'review';
    }

    /**
     * Queue a change for human review instead of applying it directly.
     *
     * @param  array<string, mixed>  $changeData  The intended change (tool-specific structure)
     * @return string JSON response for the agent
     */
    protected function queueChange(ChatContext $context, string $description, array $changeData): string
    {
        $id = DB::table('ai_agent_changesets')->insertGetId([
            'user_id'        => $context->user?->id,
            'description'    => $description,
            'changes'        => json_encode($changeData),
            'status'         => 'pending',
            'affected_count' => $changeData['affected_count'] ?? 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return json_encode([
            'result' => [
                'queued'       => true,
                'changeset_id' => $id,
                'description'  => $description,
                'message'      => 'This change has been queued for admin review. It will be applied once approved.',
            ],
        ]);
    }
}
