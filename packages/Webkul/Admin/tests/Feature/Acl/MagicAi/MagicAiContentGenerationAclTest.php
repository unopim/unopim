<?php

use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Services\Prompt\Prompt;

it('content endpoint returns 403 when user lacks ai-agent permission', function () {
    $this->loginWithPermissions();

    $this->postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe a product.',
    ])->assertStatus(403);
});

it('image endpoint returns 403 when user lacks ai-agent permission', function () {
    $this->loginWithPermissions();

    $this->postJson(route('admin.magic_ai.image'), [
        'model'  => 'dall-e-3',
        'prompt' => 'Generate a product image.',
        'size'   => '1024x1024',
    ])->assertForbidden();
});

it('content endpoint is accessible to user with ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent']);

    $promptService = new class extends Prompt
    {
        public function getPrompt(string $prompt, ?int $resourceId = null, ?string $resourceType = null): string
        {
            return $prompt;
        }
    };
    app()->instance(Prompt::class, $promptService);

    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault')->andReturnSelf();
    $mock->shouldReceive('setPlatformId')->andReturnSelf();
    $mock->shouldReceive('setModel')->andReturnSelf();
    $mock->shouldReceive('setTemperature')->andReturnSelf();
    $mock->shouldReceive('setMaxTokens')->andReturnSelf();
    $mock->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mock->shouldReceive('setPrompt')->andReturnSelf();
    $mock->shouldReceive('ask')->andReturn('Generated content.');
    app()->instance('magic_ai', $mock);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe a product.',
    ])->assertOk();
});

it('platforms endpoint returns 403 when user lacks ai-agent permission', function () {
    $this->loginWithPermissions();

    $this->getJson(route('admin.magic_ai.platforms'))
        ->assertStatus(403);
});

it('platforms endpoint is accessible to user with ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent']);

    $this->getJson(route('admin.magic_ai.platforms'))
        ->assertOk()
        ->assertJsonStructure(['platforms']);
});

it('suggestion values endpoint returns 403 when user lacks ai-agent permission', function () {
    $this->loginWithPermissions();

    $this->getJson(route('admin.magic_ai.suggestion_values'))
        ->assertStatus(403);
});

it('suggestion values endpoint is accessible to user with ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent']);

    $this->getJson(route('admin.magic_ai.suggestion_values'))
        ->assertOk();
});

it('default prompt endpoint returns 403 when user lacks ai-agent permission', function () {
    $this->loginWithPermissions();

    $this->getJson(route('admin.magic_ai.default_prompt'))
        ->assertStatus(403);
});

it('default prompt endpoint is accessible to user with ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent']);

    $this->getJson(route('admin.magic_ai.default_prompt'))
        ->assertOk()
        ->assertJsonStructure(['prompts']);
});
