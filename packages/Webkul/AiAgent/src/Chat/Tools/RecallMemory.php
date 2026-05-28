<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class RecallMemory implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            public function name(): string
            {
                return 'recall_memory';
            }

            public function description(): string
            {
                return 'Recall previously saved facts and observations. Use this before taking actions to check if there are relevant memories about conventions, preferences, or past decisions.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'search' => $schema->string()->description('Search term to find relevant memories (searches key and value)'),
                    'scope'  => $schema->string()->enum(['user', 'catalog', 'global', 'all'])->description('Filter by scope'),
                ];
            }

            public function handle(Request $request): string
            {
                $search = $request->string('search')->toString() ?: null;
                $scope = $request->string('scope')->toString() ?: 'all';

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
                $qb->where(function ($q) {
                    $q->whereNull('user_id')
                        ->orWhere('user_id', $this->context->user?->id);
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
            }
        };
    }
}
