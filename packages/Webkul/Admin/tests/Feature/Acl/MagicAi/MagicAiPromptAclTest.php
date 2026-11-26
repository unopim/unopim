<?php

use Illuminate\Http\UploadedFile;
use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;

it('should hide edit and delete actions in prompt datagrid when user lacks those permissions', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    MagicPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->not->toContain('icon-edit');
    expect($icons)->not->toContain('icon-delete');
});

it('should show edit and delete actions in prompt datagrid when user has those permissions', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit', 'ai-agent.prompt.delete']);

    MagicPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->toContain('icon-edit');
    expect($icons)->toContain('icon-delete');
});

it('should hide edit and delete actions in system prompt datagrid when user lacks those permissions', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

    MagicAISystemPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.system_prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->not->toContain('icon-edit');
    expect($icons)->not->toContain('icon-delete');
});

it('should show edit and delete actions in system prompt datagrid when user has those permissions', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit', 'ai-agent.system-prompt.delete']);

    MagicAISystemPrompt::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.magic_ai.system_prompt.index'));

    $response->assertOk();

    $icons = collect($response->json('actions'))->pluck('icon')->toArray();

    expect($icons)->toContain('icon-edit');
    expect($icons)->toContain('icon-delete');
});

it('should restrict system prompt store route when user lacks permission', function (): void {
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

it('should allow system prompt store route when user has permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.create']);

    $response = $this->postJson(route('admin.magic_ai.system_prompt.store'), [
        'title'       => 'Test Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict system prompt update route when user lacks permission', function (): void {
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

it('should allow system prompt update route when user has permission', function (): void {
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

it('should restrict prompt store route when user lacks permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    $response = $this->postJson(route('admin.magic_ai.prompt.store'), [
        'title'       => 'Test Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    $response->assertStatus(403);
});

it('should allow prompt store route when user has permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.create']);

    $response = $this->postJson(route('admin.magic_ai.prompt.store'), [
        'title'       => 'Test Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict prompt update route when user lacks permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    $response = $this->putJson(route('admin.magic_ai.prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    $response->assertStatus(403);
});

it('should allow prompt update route when user has permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit']);

    $response = $this->putJson(route('admin.magic_ai.prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'prompt'      => 'Test Prompt text',
    ]);

    expect($response->status())->not->toBe(403);
});

it('should not let the edit permission create a prompt', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.edit']);

    $this->postJson(route('admin.magic_ai.prompt.store'), ['title' => 'Test Prompt', 'prompt' => 'Test Prompt text'])
        ->assertStatus(403);
});

it('should not let the edit permission create a system prompt', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.edit']);

    $this->postJson(route('admin.magic_ai.system_prompt.store'), [
        'title'       => 'Test Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ])->assertStatus(403);
});

it('should not let the create permission edit a prompt', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.create']);

    $this->putJson(route('admin.magic_ai.prompt.update'), ['id' => 1, 'title' => 'x', 'prompt' => 'y'])
        ->assertStatus(403);
});

it('should not let the create permission edit a system prompt', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt', 'ai-agent.system-prompt.create']);

    $this->putJson(route('admin.magic_ai.system_prompt.update'), [
        'id'          => 1,
        'title'       => 'x',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ])->assertStatus(403);
});

it('should let a role with only the prompt create permission upload an editor image', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.create']);

    $this->postJson(route('admin.tinymce.upload'), ['file' => UploadedFile::fake()->image('photo.png', 10, 10)])
        ->assertStatus(200);
});

it('should hide the create button on the prompt and system prompt pages without the create permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.system-prompt']);

    $this->get(route('admin.magic_ai.prompt.index'))
        ->assertOk()
        ->assertDontSee(trans('admin::app.configuration.prompt.create.create-btn'));

    $this->get(route('admin.magic_ai.system_prompt.index'))
        ->assertOk()
        ->assertDontSee(trans('admin::app.configuration.system-prompt.create.create-btn'));
});

it('should show the create button on the prompt and system prompt pages with the create permission', function (): void {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt', 'ai-agent.prompt.create', 'ai-agent.system-prompt', 'ai-agent.system-prompt.create']);

    $this->get(route('admin.magic_ai.prompt.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.configuration.prompt.create.create-btn'));

    $this->get(route('admin.magic_ai.system_prompt.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.configuration.system-prompt.create.create-btn'));
});
