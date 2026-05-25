<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

it('should display the magic ai platform index page if has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);

    $this->get(route('admin.magic_ai.platform.index'))
        ->assertOk();
});

it('should not display the magic ai platform index page if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.magic_ai.platform.index'))
        ->assertStatus(403);
});

it('should display the edit magic ai platform page if has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.edit']);

    $platform = MagicAIPlatform::create([
        'label'    => 'Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => 1,
    ]);

    $this->get(route('admin.magic_ai.platform.edit', $platform->id))
        ->assertOk();
});

it('should not display the edit magic ai platform page if does not have permission', function () {
    $this->loginWithPermissions();

    $platform = MagicAIPlatform::create([
        'label'    => 'Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => 1,
    ]);

    $this->get(route('admin.magic_ai.platform.edit', $platform->id))
        ->assertStatus(403);
});

it('should hide edit and delete actions in platform datagrid when user lacks those permissions', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);

    MagicAIPlatform::create([
        'label'    => 'Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => 1,
    ]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.platform.index'));

    $response->assertOk();

    $actions = $response->json('actions');
    $icons = collect($actions)->pluck('icon')->toArray();

    expect($icons)->not->toContain('icon-edit');
    expect($icons)->not->toContain('icon-delete');
});

it('should show edit and delete actions in platform datagrid when user has those permissions', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.edit', 'ai-agent.platform.delete']);

    MagicAIPlatform::create([
        'label'    => 'Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => 1,
    ]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.platform.index'));

    $response->assertOk();

    $actions = $response->json('actions');
    $icons = collect($actions)->pluck('icon')->toArray();

    expect($icons)->toContain('icon-edit');
    expect($icons)->toContain('icon-delete');
});
