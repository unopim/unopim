<?php

/**
 * Every `trans.someKey` the chat widget template reads must exist in the
 * component's `trans` object, otherwise Vue renders the literal "undefined"
 * next to the value (as the session list did with its message count).
 */
it('defines every translation key the chat widget template references', function () {
    $path = base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php');

    expect($path)->toBeReadableFile();

    $template = (string) file_get_contents($path);

    preg_match_all('/\btrans\.([A-Za-z0-9_]+)/', $template, $usedMatches);
    preg_match_all('/^\s+([A-Za-z0-9_]+):\s*(?:`@lang\(|\{)/m', $template, $definedMatches);

    $used = array_unique($usedMatches[1]);
    $defined = $definedMatches[1];

    $missing = array_values(array_diff($used, $defined));

    expect($missing)->toBe([]);
});

it('exposes the session message-count label', function () {
    $template = (string) file_get_contents(
        base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php')
    );

    expect($template)->toContain("messages: `@lang('ai-agent::app.widget.messages')`");
    expect(trans('ai-agent::app.widget.messages'))->not->toBe('ai-agent::app.widget.messages');
});
