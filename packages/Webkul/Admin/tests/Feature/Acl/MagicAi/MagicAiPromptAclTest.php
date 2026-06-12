<?php

/**
 * Regression coverage for the MagicAI prompt / system-prompt authorization bypass
 * (security findings #9 and #10).
 *
 * The `store` and `update` routes for both `admin.magic_ai.prompt` and
 * `admin.magic_ai.system_prompt` were registered but absent from
 * AiAgent/Config/acl.php, so the fail-open Bouncer middleware skipped the
 * permission check and any authenticated admin could create or overwrite a
 * prompt. The fix maps those routes to the existing `ai-agent.prompt` /
 * `ai-agent.system-prompt` permissions (2.0 has no finer-grained edit node),
 * so an admin without that permission is now forbidden.
 */
it('should restrict prompt store route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->postJson(route('admin.magic_ai.prompt.store'), [
        'title'  => 'Test Prompt',
        'prompt' => 'Test Prompt text',
    ]);

    $response->assertStatus(401);
});

it('should allow prompt store route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    $response = $this->postJson(route('admin.magic_ai.prompt.store'), [
        'title'  => 'Test Prompt',
        'prompt' => 'Test Prompt text',
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict prompt update route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->putJson(route('admin.magic_ai.prompt.update'), [
        'id'     => 1,
        'title'  => 'Updated Prompt',
        'prompt' => 'Test Prompt text',
    ]);

    $response->assertStatus(401);
});

it('should allow prompt update route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.prompt']);

    $response = $this->putJson(route('admin.magic_ai.prompt.update'), [
        'id'     => 1,
        'title'  => 'Updated Prompt',
        'prompt' => 'Test Prompt text',
    ]);

    expect($response->status())->not->toBe(403);
});

it('should restrict system prompt store route when user lacks permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->postJson(route('admin.magic_ai.system_prompt.store'), [
        'title'       => 'Test Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    $response->assertStatus(401);
});

it('should allow system prompt store route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

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
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->putJson(route('admin.magic_ai.system_prompt.update'), [
        'id'          => 1,
        'title'       => 'Updated Prompt',
        'tone'        => 'professional',
        'is_enabled'  => 1,
        'max_tokens'  => 1000,
        'temperature' => 1.0,
    ]);

    $response->assertStatus(401);
});

it('should allow system prompt update route when user has permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.system-prompt']);

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
