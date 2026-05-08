<?php

$view = __DIR__.'/../../../src/Resources/views/components/tinymce/index.blade.php';

it('should register the TinyMCE aibutton only when text generation is enabled', function () use ($view) {
    $contents = file_get_contents($view);

    expect($contents)->toContain("const toolbar1 = self.ai.enabled ? baseToolbar + ' | aibutton' : baseToolbar;");
    expect($contents)->toContain('if (self.ai.enabled) {');
    // The aibutton registration must sit inside the ai.enabled guard
    $aiBlockStart = strpos($contents, 'if (self.ai.enabled) {');
    $buttonIndex = strpos($contents, "addButton('aibutton'");
    expect($buttonIndex)->toBeGreaterThan($aiBlockStart);
});

it('should notify VeeValidate on keyup, change, and input events so paste clears validation errors', function () use ($view) {
    $contents = file_get_contents($view);

    // All three events must appear in a single editor.on() call so that
    // paste operations (which do not reliably fire keyup) still clear
    // the required-field validation error.
    expect($contents)->toContain("editor.on('keyup change input'");
});
