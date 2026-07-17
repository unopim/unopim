<?php

use Webkul\MagicAI\Services\ScopedProviderConfig;

it('applies overrides only for the duration of the callback', function () {
    config(['ai.providers.openai.key' => 'original-key']);

    $insideKey = null;
    $insideUrl = null;

    ScopedProviderConfig::run('openai', ['key' => 'scoped-key', 'url' => 'https://example.test/v1'], function () use (&$insideKey, &$insideUrl) {
        $insideKey = config('ai.providers.openai.key');
        $insideUrl = config('ai.providers.openai.url');
    });

    expect($insideKey)->toBe('scoped-key');
    expect($insideUrl)->toBe('https://example.test/v1');
    expect(config('ai.providers.openai.key'))->toBe('original-key');
    expect(config('ai.providers.openai.url'))->toBeNull();
});

it('restores the original config even when the callback throws', function () {
    config(['ai.providers.openai.key' => 'original-key']);

    expect(fn () => ScopedProviderConfig::run('openai', ['key' => 'scoped-key'], function () {
        throw new RuntimeException('provider exploded');
    }))->toThrow(RuntimeException::class);

    expect(config('ai.providers.openai.key'))->toBe('original-key');
});

it('returns the callback result', function () {
    $result = ScopedProviderConfig::run('openai', ['key' => 'scoped-key'], fn () => 'generated');

    expect($result)->toBe('generated');
});
