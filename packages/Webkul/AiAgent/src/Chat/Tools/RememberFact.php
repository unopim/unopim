<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class RememberFact implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            public function name(): string
            {
                return 'remember_fact';
            }

            public function description(): string
            {
                return 'Save an observation or fact for future reference. Use this to remember catalog patterns, user preferences, naming conventions, or important decisions.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'key'   => $schema->string()->description('Short key/label for this fact (e.g. "naming_convention", "preferred_category_structure")'),
                    'value' => $schema->string()->description('The fact or observation to remember'),
                    'scope' => $schema->string()->enum(['user', 'catalog', 'global'])->description('Scope of this memory'),
                ];
            }

            public function handle(Request $request): string
            {
                $key = $request->string('key')->toString();
                $value = $request->string('value')->toString();
                $scope = $request->string('scope')->toString() ?: 'catalog';

                $userId = $scope === 'user' ? $this->context->user?->id : null;

                $existing = DB::table('ai_agent_memories')
                    ->where('scope', $scope)
                    ->where('key', $key)
                    ->where('user_id', $userId)
                    ->first();

                if ($existing) {
                    DB::table('ai_agent_memories')->where('id', $existing->id)->update([
                        'value'      => $value,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('ai_agent_memories')->insert([
                        'scope'      => $scope,
                        'key'        => $key,
                        'user_id'    => $userId,
                        'value'      => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return json_encode(['result' => ['remembered' => true, 'key' => $key, 'scope' => $scope]]);
            }
        };
    }
}
