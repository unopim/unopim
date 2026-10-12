<?php

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
