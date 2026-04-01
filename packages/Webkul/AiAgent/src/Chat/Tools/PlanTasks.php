<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class PlanTasks implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('plan_tasks')
            ->for('Create a multi-step task plan for complex operations. Use this when the user asks for something that requires multiple steps (e.g. "make all products market-ready"). Present the plan to the user for approval before executing.')
            ->withStringParameter('goal', 'The high-level goal to achieve')
            ->withStringParameter('steps_json', 'JSON array of step objects: [{"title":"Step title","description":"What to do","tool":"tool_to_use"}]')
            ->using(function (string $goal, string $steps_json) use ($context): string {
                $steps = json_decode($steps_json, true);

                if (empty($steps) || ! is_array($steps)) {
                    return json_encode(['error' => 'Invalid steps JSON']);
                }

                // Create parent task
                $parentId = DB::table('ai_agent_tasks')->insertGetId([
                    'type'       => 'planned_workflow',
                    'status'     => 'pending',
                    'priority'   => 'normal',
                    'config'     => json_encode(['goal' => $goal, 'total_steps' => count($steps)]),
                    'created_by' => $context->user?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create child tasks
                foreach ($steps as $i => $step) {
                    DB::table('ai_agent_tasks')->insert([
                        'type'           => 'planned_step',
                        'status'         => 'pending',
                        'priority'       => 'normal',
                        'config'         => json_encode([
                            'step_number' => $i + 1,
                            'title'       => $step['title'] ?? 'Step '.($i + 1),
                            'description' => $step['description'] ?? '',
                            'tool'        => $step['tool'] ?? null,
                        ]),
                        'parent_task_id' => $parentId,
                        'created_by'     => $context->user?->id,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }

                return json_encode([
                    'result' => [
                        'task_id'     => $parentId,
                        'goal'        => $goal,
                        'total_steps' => count($steps),
                        'steps'       => array_map(fn ($s, $i) => [
                            'step'        => $i + 1,
                            'title'       => $s['title'] ?? 'Step '.($i + 1),
                            'description' => $s['description'] ?? '',
                        ], $steps, array_keys($steps)),
                        'status'      => 'Plan created. Present this to the user and ask for approval before executing each step.',
                    ],
                ]);
            });
    }
}
