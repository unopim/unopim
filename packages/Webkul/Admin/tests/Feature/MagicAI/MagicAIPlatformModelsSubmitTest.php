<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

it('should accept a comma-separated models string when creating a platform (Issue #685)', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Models String '.uniqid(),
        'provider' => 'openai',
        'api_url'  => 'https://example.test',
        'api_key'  => 'sk-test',
        'models'   => 'gpt-4,gpt-4o-mini',
        'status'   => 1,
    ]);

    $response->assertStatus(200);

    expect(MagicAIPlatform::latest('id')->first()->models)->toContain('gpt-4');
});

it('should always set the models field in the platform form FormData submit path', function () {
    $view = file_get_contents(__DIR__.'/../../../src/Resources/views/configuration/magic-ai/platform/index.blade.php');

    expect($view)->toContain("saveData.set('models', this.selectedModels.join(','));");
});
