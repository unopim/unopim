<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Services\ScopedProviderConfig;

beforeEach(function () {
    config(['ai.providers.openai' => ['key' => 'env-key', 'url' => 'https://api.openai.com/v1']]);
});

it('applies the overrides only for the duration of the callback', function () {
    $seen = ScopedProviderConfig::run('openai', ['key' => 'platform-key', 'url' => 'https://1.1.1.1/v1'], fn (): array => config('ai.providers.openai'));

    expect($seen)->toBe(['key' => 'platform-key', 'url' => 'https://1.1.1.1/v1'])
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
 * Send one request through the Http client and return the options it carried.
 *
 * @return array<string, mixed>
 */
function sentHttpOptions(string $url = 'https://1.1.1.1/v1/models'): array
{
    $sent = [];

    Http::fake(function (Request $request, array $options) use (&$sent) {
        $sent = $options;

        return Http::response([]);
    });

    Http::get($url);

    return $sent;
}

it('pins a platform base url and turns redirects off for the provider call', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], fn (): array => sentHttpOptions());

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen['curl'][CURLOPT_RESOLVE])->toBe(['1.1.1.1:443:1.1.1.1'])
        ->and(sentHttpOptions())->not->toHaveKey('curl');
});

it('pins only requests to the platform host', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], fn (): array => sentHttpOptions('https://8.8.8.8/v1/models'));

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen)->not->toHaveKey('curl');
});

it('turns redirects off even when the provider uses its default url', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['key' => 'platform-key'], fn (): array => sentHttpOptions());

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen)->not->toHaveKey('curl');
});

it('refuses a platform url that cannot be validated and pinned, before changing any config', function (string $url): void {
    $called = false;

    expect(fn () => ScopedProviderConfig::run('openai', ['key' => 'platform-key', 'url' => $url], function () use (&$called): void {
        $called = true;
    }))->toThrow(RuntimeException::class, trans('admin::app.configuration.platform.message.unsafe-api-url'));

    expect($called)->toBeFalse()
        ->and(config('ai.providers.openai'))->toBe(['key' => 'env-key', 'url' => 'https://api.openai.com/v1']);
})->with([
    'metadata address' => ['https://169.254.169.254/latest'],
    'loopback'         => ['https://127.0.0.1/v1'],
    'unresolvable'     => ['https://unresolvable.invalid/v1'],
]);

it('stops guarding once the provider call throws', function (): void {
    expect(fn () => ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], function (): void {
        throw new RuntimeException('provider exploded');
    }))->toThrow(RuntimeException::class);

    $after = sentHttpOptions();

    expect($after)->not->toHaveKey('curl')
        ->and($after['allow_redirects'] ?? null)->not->toBeFalse();
});

it('strips the location of a redirect answered inside a provider call', function (): void {
    Http::fake(['*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/'])]);

    $response = ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], fn () => Http::get('https://1.1.1.1/v1/models'));

    expect($response->status())->toBe(302)
        ->and($response->header('Location'))->toBe('');
});

it('leaves the local Ollama daemon default unpinned, with redirects still off', function (): void {
    $seen = ScopedProviderConfig::run('ollama', ['url' => 'http://localhost:11434'], fn (): array => sentHttpOptions('http://localhost:11434/api/chat'));

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen)->not->toHaveKey('curl');
});

it('refuses a loopback url for any provider other than the Ollama default', function (): void {
    expect(fn () => ScopedProviderConfig::run('openai', ['url' => 'http://localhost:11434'], fn () => null))
        ->toThrow(RuntimeException::class);
});
