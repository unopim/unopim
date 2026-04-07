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
        ->assertSeeText('Unauthorized');
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
        ->assertSeeText('Unauthorized');
});
