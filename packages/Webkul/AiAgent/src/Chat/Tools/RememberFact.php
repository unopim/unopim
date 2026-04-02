<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class RememberFact implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('remember_fact')
            ->for('Save an observation or fact for future reference. Use this to remember catalog patterns, user preferences, naming conventions, or important decisions.')
            ->withStringParameter('key', 'Short key/label for this fact (e.g. "naming_convention", "preferred_category_structure")')
            ->withStringParameter('value', 'The fact or observation to remember')
            ->withEnumParameter('scope', 'Scope of this memory', ['user', 'catalog', 'global'])
            ->using(function (string $key, string $value, string $scope = 'catalog') use ($context): string {
                $userId = $scope === 'user' ? $context->user?->id : null;

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
            });
    }
}
