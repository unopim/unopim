<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

it('should accept model names prefixed with tilde by stripping them before validation', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'OpenRouter Tilde '.uniqid(),
        'provider' => 'openrouter',
        'api_url'  => 'https://openrouter.ai/api/v1',
        'api_key'  => 'sk-or-test',
        'models'   => '~anthropic/claude-opus-latest, openai/gpt-4',
        'status'   => 1,
    ]);

    $response->assertStatus(200);

    $stored = MagicAIPlatform::latest('id')->first();

    expect($stored->models)->not->toContain('~');
    expect($stored->models)->toContain('anthropic/claude-opus-latest');
});
