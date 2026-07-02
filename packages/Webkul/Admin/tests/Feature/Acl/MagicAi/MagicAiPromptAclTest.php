<?php

use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;

it('should hide edit and delete actions in prompt datagrid when user lacks those permissions', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    MagicPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->not->toContain('icon-edit');
    expect($icons)->not->toContain('icon-delete');
});

it('should show edit and delete actions in prompt datagrid when user has those permissions', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit', 'ai-agent.prompt.delete']);

    MagicPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->toContain('icon-edit');
    expect($icons)->toContain('icon-delete');
});

it('should hide edit and delete actions in system prompt datagrid when user lacks those permissions', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

    MagicAISystemPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.system_prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->not->toContain('icon-edit');
    expect($icons)->not->toContain('icon-delete');
});

it('should show edit and delete actions in system prompt datagrid when user has those permissions', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit', 'ai-agent.system-prompt.delete']);

    MagicAISystemPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.system_prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->toContain('icon-edit');
    expect($icons)->toContain('icon-delete');
});

it('should restrict system prompt store route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

    $response = $this->postJson(route('admin.magic_ai.system_prompt.store'), [
        'title'       => 'Test Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    $response->assertStatus(403);
});

it('should allow system prompt store route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit']);

    $response = $this->postJson(route('admin.magic_ai.system_prompt.store'), [
        'title'       => 'Test Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict system prompt update route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

    $response = $this->putJson(route('admin.magic_ai.system_prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    $response->assertStatus(403);
});

it('should allow system prompt update route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit']);

    $response = $this->putJson(route('admin.magic_ai.system_prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict prompt store route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    $response = $this->postJson(route('admin.magic_ai.prompt.store'), [
        'title'       => 'Test Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    $response->assertStatus(403);
});

it('should allow prompt store route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit']);

    $response = $this->postJson(route('admin.magic_ai.prompt.store'), [
        'title'       => 'Test Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict prompt update route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    $response = $this->putJson(route('admin.magic_ai.prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    $response->assertStatus(403);
});

it('should allow prompt update route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit']);

    $response = $this->putJson(route('admin.magic_ai.prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    expect($response->status())->not->toBe(403);
});
