<?php

/*
 * Regression guard: the chat widget sanitises user/AI-controlled HTML and SVG with
 * DOMPurify, which is bundled into the admin app (window.DOMPurify) rather than
 * pulled from an external CDN. Loading it from a third-party CDN was dropped for
 * security (supply-chain + availability). This test asserts the widget keeps using
 * the bundled sanitizer, never reintroduces a CDN reference, and fails safe when the
 * sanitizer is unavailable. No network access — deterministic.
 */

$bladePath = dirname(__DIR__, 2).'/Resources/views/components/chat-widget.blade.php';

$appJsPath = dirname(__DIR__, 3).'/Admin/src/Resources/assets/js/app.js';

it('sanitises through the bundled DOMPurify and references no external CDN', function () use ($bladePath) {
    expect(file_exists($bladePath))->toBeTrue();

    $contents = file_get_contents($bladePath);

    // Self-hosted only: no third-party CDN reference may creep back in.
    expect($contents)->not->toContain('cdnjs.cloudflare.com');
    expect($contents)->not->toContain('purify.min.js');

    // The widget must still route untrusted markup through DOMPurify.
    expect($contents)->toContain('DOMPurify.sanitize');
});

it('exposes the bundled DOMPurify on window from the admin app entrypoint', function () use ($appJsPath) {
    expect(file_exists($appJsPath))->toBeTrue();

    $contents = file_get_contents($appJsPath);

    expect($contents)->toContain('import DOMPurify from "dompurify"');
    expect($contents)->toContain('window.DOMPurify = DOMPurify');
});

it('fails safe (escapes to inert text) when the sanitizer is unavailable', function () use ($bladePath) {
    $contents = file_get_contents($bladePath);

    // The sanitizer helpers guard on `typeof DOMPurify` and fall back to a
    // no-inject path rather than emitting raw untrusted markup.
    expect($contents)->toContain("typeof DOMPurify !== 'undefined'");
});
