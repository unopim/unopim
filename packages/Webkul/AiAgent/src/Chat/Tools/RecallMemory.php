<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class RecallMemory implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('recall_memory')
            ->for('Recall previously saved facts and observations. Use this before taking actions to check if there are relevant memories about conventions, preferences, or past decisions.')
            ->withStringParameter('search', 'Search term to find relevant memories (searches key and value)')
            ->withEnumParameter('scope', 'Filter by scope', ['user', 'catalog', 'global', 'all'])
            ->using(function (?string $search = null, string $scope = 'all') use ($context): string {
                $qb = DB::table('ai_agent_memories')
                    ->select('scope', 'key', 'value', 'updated_at')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });

                if ($scope !== 'all') {
                    $qb->where('scope', $scope);
                }

                // Include user-specific and global memories
                $qb->where(function ($q) use ($context) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', $context->user?->id);
                });

                if ($search) {
                    $qb->where(function ($q) use ($search) {
                        $q->where('key', 'like', "%{$search}%")
                            ->orWhere('value', 'like', "%{$search}%");
                    });
                }

                $memories = $qb->orderByDesc('updated_at')->limit(20)->get();

                return json_encode([
                    'total'    => $memories->count(),
                    'memories' => $memories->toArray(),
                ]);
            });
    }
}
