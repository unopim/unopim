<?php

$view = __DIR__.'/../../../src/Resources/views/configuration/magic-ai/platform/index.blade.php';

it('should auto-fetch models on api_key input with a debounced handler (Issue #761)', function () use ($view) {
    $contents = file_get_contents($view);

    expect($contents)->toContain('onApiKeyInput($event)');
    expect($contents)->toContain('_apiKeyInputTimer');
    expect($contents)->toContain('this.onApiKeyEntered()');
});
