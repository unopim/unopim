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
