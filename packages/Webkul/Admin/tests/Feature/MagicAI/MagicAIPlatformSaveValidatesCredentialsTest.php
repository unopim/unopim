<?php

$view = __DIR__.'/../../../src/Resources/views/configuration/magic-ai/platform/index.blade.php';

it('should call the test-connection endpoint before saving a Magic AI platform (Issue #760)', function () use ($view) {
    $contents = file_get_contents($view);

    expect($contents)->toContain('admin.magic_ai.platform.test');
    expect($contents)->toContain('saveWithTest');
    // Guard: if api_key is masked (untouched on edit), skip the test.
    expect($contents)->toContain('keyLooksMasked');
});
