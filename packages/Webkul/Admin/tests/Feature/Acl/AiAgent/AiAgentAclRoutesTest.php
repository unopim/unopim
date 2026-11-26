<?php

use Illuminate\Support\Facades\Route;
use Webkul\AiAgent\Models\Credential;

/**
 * Create an active AI Agent credential.
 */
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

/**
 * Build a valid credential form payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function aiAgentCredentialPayload(array $overrides = []): array
{
    return [
        'label'    => 'Saved credential',
        'provider' => 'openai',
        'apiUrl'   => 'https://1.1.1.1/v1',
        'apiKey'   => 'sk-new-key',
        'model'    => 'gpt-4o-mini',
        'status'   => true,
        ...$overrides,
    ];
}

/**
 * Route names the ai agent acl config points to.
 *
 * @return array<int, string>
 */
function aiAgentAclRoutes(): array
{
    return collect(include base_path('packages/Webkul/AiAgent/Config/acl.php'))
        ->pluck('route')
        ->filter()
        ->values()
        ->all();
}

it('registers every route the ai agent acl config points to', function (): void {
    $missing = collect(aiAgentAclRoutes())
        ->reject(fn (string $route): bool => Route::has($route))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('maps every ai agent route in the acl config', function (): void {
    $unmapped = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_starts_with($name, 'ai-agent.'))
        ->diff(aiAgentAclRoutes())
        ->values()
        ->all();

    expect($unmapped)->toBe([]);
});

it('shows the create agent button to a custom role granted agents', function (): void {
    $this->loginWithPermissions(permissions: ['ai-agent', 'ai-agent.agents']);

    $this->get(route('ai-agent.agents.index'))
        ->assertOk()
        ->assertSee(route('ai-agent.agents.create'), false);
});

it('opens the credentials pages for a custom role granted credentials', function (): void {
    $this->loginWithPermissions(permissions: ['ai-agent', 'ai-agent.credentials']);

    $credential = makeAiAgentCredential();

    $this->get(route('ai-agent.credentials.index'))
        ->assertOk()
        ->assertSee(route('ai-agent.credentials.create'), false);

    $this->get(route('ai-agent.credentials.create'))->assertOk();

    $this->get(route('ai-agent.credentials.edit', $credential->id))->assertOk();
});

it('stores a credential for a custom role granted credentials', function (): void {
    $this->loginWithPermissions(permissions: ['ai-agent', 'ai-agent.credentials']);

    $this->postJson(route('ai-agent.credentials.store'), aiAgentCredentialPayload())
        ->assertOk()
        ->assertJsonPath('redirect_url', route('ai-agent.credentials.index'));

    expect(Credential::query()->where('label', 'Saved credential')->first()?->apiKey)->toBe('sk-new-key');
});

it('keeps the stored key when an update sends the masked placeholder', function (): void {
    $this->loginWithPermissions(permissions: ['ai-agent', 'ai-agent.credentials']);

    $credential = makeAiAgentCredential();

    $this->putJson(route('ai-agent.credentials.update', $credential->id), aiAgentCredentialPayload([
        'label'  => 'Renamed credential',
        'apiKey' => '********',
    ]))
        ->assertOk()
        ->assertJsonPath('redirect_url', route('ai-agent.credentials.index'));

    $credential->refresh();

    expect($credential->label)->toBe('Renamed credential')
        ->and($credential->apiKey)->toBe('sk-test');
});

it('redirects to the credentials listing after deleting a credential', function (): void {
    $this->loginWithPermissions(permissions: ['ai-agent', 'ai-agent.credentials']);

    $credential = makeAiAgentCredential();

    $this->deleteJson(route('ai-agent.credentials.destroy', $credential->id))
        ->assertOk()
        ->assertJsonPath('redirect_url', route('ai-agent.credentials.index'));

    expect(Credential::query()->find($credential->id))->toBeNull();
});

it('forbids the credentials routes without the credentials permission', function (string $method, string $route, bool $withId): void {
    $this->loginWithPermissions(permissions: ['ai-agent', 'ai-agent.agents']);

    $credential = makeAiAgentCredential();

    $url = $withId ? route($route, $credential->id) : route($route);

    $this->json($method, $url, aiAgentCredentialPayload(['label' => 'Forbidden change']))->assertForbidden();

    $credential->refresh();

    expect($credential->label)->toBe('Test credential')
        ->and(Credential::query()->count())->toBe(1);
})->with([
    'index'   => ['GET', 'ai-agent.credentials.index', false],
    'create'  => ['GET', 'ai-agent.credentials.create', false],
    'store'   => ['POST', 'ai-agent.credentials.store', false],
    'edit'    => ['GET', 'ai-agent.credentials.edit', true],
    'update'  => ['PUT', 'ai-agent.credentials.update', true],
    'destroy' => ['DELETE', 'ai-agent.credentials.destroy', true],
]);
