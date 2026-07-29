<?php

use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;

/*
 * The prompt grids build their actions conditionally on permissions, so the
 * positional fallback index shifted whenever one was withheld: for a role with
 * view + delete only, the delete action became "action_1" — the slot the view
 * treats as edit. Clicking it navigated to the delete URL with GET and the
 * route, which only accepts DELETE, answered 405. Each action now carries a
 * stable index of its own.
 */

/**
 * @param  array<int, string>  $permissions
 */
function promptGridActions(string $route, array $permissions): array
{
    test()->loginWithPermissions(permissions: $permissions);

    $response = test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route($route));

    $response->assertOk();

    return $response->json('records.0.actions') ?? [];
}

it('keeps the prompt delete action addressable when the role withholds edit', function () {
    MagicPrompt::factory()->create();

    $actions = promptGridActions('admin.magic_ai.prompt.index', [
        'ai-agent',
        'ai-agent.prompt',
        'ai-agent.prompt.delete',
    ]);

    expect(collect($actions)->pluck('index')->all())->toBe(['delete']);

    $delete = collect($actions)->firstWhere('index', 'delete');

    expect($delete['method'])->toBe('DELETE');
});

it('indexes both prompt actions by name when the role grants edit and delete', function () {
    MagicPrompt::factory()->create();

    $actions = promptGridActions('admin.magic_ai.prompt.index', [
        'ai-agent',
        'ai-agent.prompt',
        'ai-agent.prompt.edit',
        'ai-agent.prompt.delete',
    ]);

    expect(collect($actions)->pluck('index')->all())->toBe(['edit', 'delete']);
});

it('keeps the system prompt delete action addressable when the role withholds edit', function () {
    MagicAISystemPrompt::factory()->create();

    $actions = promptGridActions('admin.magic_ai.system_prompt.index', [
        'ai-agent',
        'ai-agent.system-prompt',
        'ai-agent.system-prompt.delete',
    ]);

    expect(collect($actions)->pluck('index')->all())->toBe(['delete']);

    $delete = collect($actions)->firstWhere('index', 'delete');

    expect($delete['method'])->toBe('DELETE');
});

it('indexes both system prompt actions by name when the role grants edit and delete', function () {
    MagicAISystemPrompt::factory()->create();

    $actions = promptGridActions('admin.magic_ai.system_prompt.index', [
        'ai-agent',
        'ai-agent.system-prompt',
        'ai-agent.system-prompt.edit',
        'ai-agent.system-prompt.delete',
    ]);

    expect(collect($actions)->pluck('index')->all())->toBe(['edit', 'delete']);
});
