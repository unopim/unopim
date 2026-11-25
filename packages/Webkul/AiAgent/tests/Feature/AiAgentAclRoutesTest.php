<?php

use Illuminate\Support\Facades\Route;
use Webkul\AiAgent\Models\Credential;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * @param  array<int, string>  $permissions
 */
function actingAsAiAgentRole(array $permissions): void
{
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => $permissions]);

    test()->actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');
}

function makeAiAgentCredential(): Credential
{
    return Credential::query()->create([
        'label'    => 'Test credential',
        'provider' => 'openai',
        'apiUrl'   => 'https://api.openai.com/v1',
        'apiKey'   => 'sk-test',
        'model'    => 'gpt-4o-mini',
        'status'   => true,
    ]);
}

it('registers every route the ai agent acl config points to', function () {
    $missing = collect(include base_path('packages/Webkul/AiAgent/Config/acl.php'))
        ->pluck('route')
        ->filter()
        ->reject(fn (string $route): bool => Route::has($route))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('shows the create agent button to a custom role granted agents', function () {
    actingAsAiAgentRole(['ai-agent', 'ai-agent.agents']);

    $this->get(route('ai-agent.agents.index'))
        ->assertOk()
        ->assertSee(route('ai-agent.agents.create'), false);
});

it('opens the credentials pages for a custom role granted credentials', function () {
    actingAsAiAgentRole(['ai-agent', 'ai-agent.credentials']);

    $credential = makeAiAgentCredential();

    $this->get(route('ai-agent.credentials.index'))
        ->assertOk()
        ->assertSee(route('ai-agent.credentials.create'), false);

    $this->get(route('ai-agent.credentials.create'))->assertOk();

    $this->get(route('ai-agent.credentials.edit', $credential->id))->assertOk();
});

it('redirects to the credentials listing after deleting a credential', function () {
    actingAsAiAgentRole(['ai-agent', 'ai-agent.credentials']);

    $credential = makeAiAgentCredential();

    $this->deleteJson(route('ai-agent.credentials.destroy', $credential->id))
        ->assertOk()
        ->assertJsonPath('redirect_url', route('ai-agent.credentials.index'));

    expect(Credential::query()->find($credential->id))->toBeNull();
});

it('forbids the credentials pages without the credentials permission', function () {
    actingAsAiAgentRole(['ai-agent', 'ai-agent.agents']);

    $credential = makeAiAgentCredential();

    $this->get(route('ai-agent.credentials.index'))->assertForbidden();

    $this->deleteJson(route('ai-agent.credentials.destroy', $credential->id))->assertForbidden();

    expect(Credential::query()->find($credential->id))->not->toBeNull();
});
