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
