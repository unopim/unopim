<?php

use Illuminate\Support\Facades\Http;
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

/**
 * The options every Http client request starts from right now.
 *
 * @return array<string, mixed>
 */
function currentHttpGlobalOptions(): array
{
    return (fn (): array => value($this->globalOptions))->call(Http::getFacadeRoot());
}

it('pins a platform base url and turns redirects off for the provider call', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], fn (): array => currentHttpGlobalOptions());

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen['curl'][CURLOPT_RESOLVE])->toBe(['1.1.1.1:443:1.1.1.1'])
        ->and(currentHttpGlobalOptions())->toBe([]);
});

it('turns redirects off even when the provider uses its default url', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['key' => 'platform-key'], fn (): array => currentHttpGlobalOptions());

    expect($seen)->toBe(['allow_redirects' => false])
        ->and(currentHttpGlobalOptions())->toBe([]);
});

it('never pins an internal platform url, and still refuses redirects for it', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['url' => 'http://169.254.169.254/latest'], fn (): array => currentHttpGlobalOptions());

    expect($seen)->toBe(['allow_redirects' => false]);
});

it('restores the http options when the provider call throws', function (): void {
    expect(fn () => ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], function (): void {
        throw new RuntimeException('provider exploded');
    }))->toThrow(RuntimeException::class);

    expect(currentHttpGlobalOptions())->toBe([]);
});
