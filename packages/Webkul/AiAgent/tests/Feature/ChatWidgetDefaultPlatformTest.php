<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

it('should preselect the current default platform in the chat widget even if sessionStorage saved a different one', function () {
    config(['general.magic_ai.agentic_pim.enabled' => 1]);

    MagicAIPlatform::query()->delete();

    MagicAIPlatform::create([
        'label'      => 'OpenRouter',
        'provider'   => 'openrouter',
        'models'     => 'openrouter/model',
        'status'     => true,
        'is_default' => false,
    ]);

    $openai = MagicAIPlatform::create([
        'label'      => 'OpenAI',
        'provider'   => 'openai',
        'models'     => 'gpt-4',
        'status'     => true,
        'is_default' => true,
    ]);

    $contents = file_get_contents(__DIR__.'/../../Resources/views/components/chat-widget.blade.php');

    expect($contents)->toContain('s.selectedPlatformId === this.defaultPlatformId');
    expect($contents)->toContain('defaultPlatformId: defaultPlatformId,');
    expect($openai->is_default)->toBeTrue();
});
