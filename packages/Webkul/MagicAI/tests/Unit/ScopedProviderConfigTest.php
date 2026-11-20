<?php

use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Services\ScopedProviderConfig;

beforeEach(function () {
    config(['ai.providers.openai' => ['key' => 'env-key', 'url' => 'https://api.openai.com/v1']]);
});

it('applies the overrides only for the duration of the callback', function () {
    $seen = ScopedProviderConfig::run('openai', ['key' => 'platform-key', 'url' => 'https://proxy.test/v1'], fn (): array => config('ai.providers.openai'));

    expect($seen)->toBe(['key' => 'platform-key', 'url' => 'https://proxy.test/v1'])
        ->and(config('ai.providers.openai'))->toBe(['key' => 'env-key', 'url' => 'https://api.openai.com/v1']);
});

it('restores the original provider config when the callback throws', function () {
    expect(fn () => ScopedProviderConfig::run('openai', ['key' => 'platform-key'], function (): void {
        throw new RuntimeException('provider exploded');
    }))->toThrow(RuntimeException::class, 'provider exploded');

    expect(config('ai.providers.openai'))->toBe(['key' => 'env-key', 'url' => 'https://api.openai.com/v1']);
});

it('does not leak an override key added by the callback scope into the next call', function () {
    ScopedProviderConfig::run('openai', ['organization' => 'org-1'], fn () => null);

    expect(config('ai.providers.openai.organization'))->toBeNull();
});

it('returns the callback result', function () {
    expect(ScopedProviderConfig::run('openai', [], fn (): string => 'result'))->toBe('result');
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
