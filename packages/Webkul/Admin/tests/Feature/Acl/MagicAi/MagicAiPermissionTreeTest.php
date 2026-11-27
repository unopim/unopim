<?php

use Illuminate\Support\Collection;
use Webkul\User\Models\Role;

/** Load the Magic AI ACL entries from the package config. */
function magicAiAcl(): Collection
{
    return collect(require base_path('packages/Webkul/AiAgent/Config/acl.php'));
}

it('defines create, edit and delete children for each Magic AI area', function (string $area, array $children): void {
    $keys = magicAiAcl()->pluck('key')->unique();

    expect($keys)->toContain($area);

    foreach ($children as $child) {
        expect($keys)->toContain("{$area}.{$child}");
    }
})->with([
    'platforms'      => ['ai-agent.platform', ['create', 'edit', 'delete']],
    'prompts'        => ['ai-agent.prompt', ['create', 'edit', 'delete']],
    'system prompts' => ['ai-agent.system-prompt', ['create', 'edit', 'delete']],
]);

it('maps every Magic AI route to exactly one permission key', function (): void {
    $byRoute = magicAiAcl()->groupBy('route')->map(fn (Collection $entries): Collection => $entries->pluck('key')->unique()->values());

    expect($byRoute->filter(fn (Collection $keys): bool => $keys->count() > 1)->keys()->all())->toBe([]);
});

it('gives sibling permissions of the same parent distinct sort positions in sidebar order', function (): void {
    $sort = fn (string $key): int => magicAiAcl()->firstWhere('key', $key)['sort'];

    expect($sort('ai-agent.platform'))->toBeLessThan($sort('ai-agent.general'))
        ->and($sort('ai-agent.general'))->toBeLessThan($sort('ai-agent.prompt'))
        ->and($sort('ai-agent.prompt'))->toBeLessThan($sort('ai-agent.system-prompt'));
});

it('labels the Magic AI permissions like the sidebar menu', function (): void {
    $menu = trans('ai-agent::app.menu', [], 'en_US');
    $acl = trans('ai-agent::app.acl', [], 'en_US');

    expect($acl['platform'])->toBe($menu['platform'])
        ->and($acl['general'])->toBe($menu['settings'])
        ->and($acl['prompt'])->toBe($menu['prompt'])
        ->and($acl['system-prompt'])->toBe($menu['system-prompt']);
});

it('defines every ai-agent permission the grids and views check', function (): void {
    $keys = magicAiAcl()->pluck('key')->unique();

    foreach ([
        'ai-agent.prompt.create', 'ai-agent.prompt.edit', 'ai-agent.prompt.delete',
        'ai-agent.system-prompt.create', 'ai-agent.system-prompt.edit', 'ai-agent.system-prompt.delete',
    ] as $key) {
        expect($keys)->toContain($key);
    }
});

it('backfills the new create permissions for roles that could already do those actions', function (): void {
    $legacy = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit', 'ai-agent.system-prompt'],
    ]);

    $readOnly = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['ai-agent', 'ai-agent.prompt', 'ai-agent.system-prompt'],
    ]);

    $systemPromptEditor = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit'],
    ]);

    $migration = include base_path('packages/Webkul/User/src/Database/Migrations/2026_11_18_120000_add_magic_ai_create_edit_delete_permissions.php');
    $migration->up();
    $migration->up();

    $granted = $legacy->fresh()->permissions;

    expect($granted)->toContain('ai-agent.prompt.create')
        ->not->toContain('ai-agent.system-prompt.create')
        ->and(array_count_values($granted)['ai-agent.prompt.create'])->toBe(1);

    expect($readOnly->fresh()->permissions)->not->toContain('ai-agent.prompt.create')
        ->not->toContain('ai-agent.system-prompt.create')
        ->and($systemPromptEditor->fresh()->permissions)->toContain('ai-agent.system-prompt.create')
        ->not->toContain('ai-agent.prompt.create');
});

it('strips the hidden Magic AI permissions from custom roles and leaves the visible ones', function (): void {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['ai-agent', 'ai-agent.generate', 'ai-agent.execute', 'ai-agent.credentials', 'ai-agent.agents', 'ai-agent.dashboard', 'ai-agent.prompt', 'catalog'],
    ]);

    (include base_path('packages/Webkul/User/src/Database/Migrations/2026_11_18_120000_add_magic_ai_create_edit_delete_permissions.php'))->up();

    expect($role->fresh()->permissions)->toBe(['ai-agent', 'ai-agent.prompt', 'catalog']);
});

it('offers only platforms, settings, prompts and system prompts as Magic AI permission checkboxes', function (): void {
    $children = array_keys(app('acl')->items['ai-agent']['children']);

    expect($children)->toContain('platform', 'general', 'prompt', 'system-prompt')
        ->not->toContain('generate')
        ->not->toContain('execute')
        ->not->toContain('credentials')
        ->not->toContain('agents')
        ->not->toContain('dashboard');
});

it('keeps the hidden Magic AI routes guarded', function (string $route): void {
    expect(app('acl')->roles)->toHaveKey($route);
})->with([
    'generate page'    => 'ai-agent.generate.index',
    'generate process' => 'ai-agent.generate.process',
    'execute'          => 'ai-agent.execute',
    'agents list'      => 'ai-agent.agents.index',
    'agent create'     => 'ai-agent.agents.create',
    'agent destroy'    => 'ai-agent.agents.destroy',
    'dashboard'        => 'ai-agent.dashboard.analytics',
    'rollback'         => 'ai-agent.dashboard.rollback',
]);

it('forbids the hidden Magic AI pages for a role limited to the visible permissions', function (string $route): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.general', 'ai-agent.prompt', 'ai-agent.system-prompt']);

    $this->getJson(route($route))->assertStatus(403);
})->with(['ai-agent.generate.index', 'ai-agent.agents.index', 'ai-agent.dashboard.analytics']);
