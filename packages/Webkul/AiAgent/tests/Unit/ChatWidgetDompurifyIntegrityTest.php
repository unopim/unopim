<?php

/*
 * Regression guard: the chat widget loads DOMPurify from cdnjs with a Subresource
 * Integrity (SRI) hash. A wrong hash makes every browser BLOCK the script, which
 * leaves window.DOMPurify undefined, throws in the Vue bundle, and puts the whole
 * admin panel into a reload loop (the chat widget renders on every admin page).
 *
 * cdnjs files are immutable per version, so the SRI hash for a pinned version is a
 * constant. This test pins the correct SHA-384 for dompurify 3.1.0 and asserts the
 * blade uses it. No network access — deterministic.
 */

$bladePath = dirname(__DIR__, 2).'/Resources/views/components/chat-widget.blade.php';

$correctIntegrity = 'sha384-0Olea29UkyQZfNulDPUf5MshXT3rXNrDqk+TXn1sY6UoVDiqIfEERRKn8oMgO9eF';

it('references the DOMPurify CDN with the correct SRI hash so the browser does not block it', function () use ($bladePath, $correctIntegrity) {
    expect(file_exists($bladePath))->toBeTrue();

    $contents = file_get_contents($bladePath);

    expect($contents)->toContain('cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.0/purify.min.js');
    expect($contents)->toContain($correctIntegrity);
});

it('does not carry the known-broken DOMPurify SRI hash', function () use ($bladePath) {
    $contents = file_get_contents($bladePath);

    expect($contents)->not->toContain('sha384-/knAMB4gMqm3mPGf8xMfFjCF0Fw3GMdmF6Bj25kjGp9TzFKGefvtsYzn/7BNEUU');
});
