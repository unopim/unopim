<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Services\ScopedProviderConfig;
use Webkul\Webhook\Validators\SafeWebhookUrl;

it('applies overrides only for the duration of the callback', function () {
    config(['ai.providers.openai.key' => 'original-key']);

    $insideKey = null;
    $insideUrl = null;

    ScopedProviderConfig::run('openai', ['key' => 'scoped-key', 'url' => 'https://1.1.1.1/v1'], function () use (&$insideKey, &$insideUrl) {
        $insideKey = config('ai.providers.openai.key');
        $insideUrl = config('ai.providers.openai.url');
    });

    expect($insideKey)->toBe('scoped-key');
    expect($insideUrl)->toBe('https://1.1.1.1/v1');
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

it('refuses a request to any other host while a platform url is in scope', function (): void {
    Http::fake();

    expect(fn () => ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], fn () => Http::get('https://8.8.8.8/v1/models')))
        ->toThrow(RuntimeException::class, trans('admin::app.configuration.platform.message.unsafe-api-url'));

    Http::assertNothingSent();
});

it('lets a request already pinned to its own host by another guarded sink through', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['url' => 'https://1.1.1.1/v1'], function (): array {
        $sent = [];

        Http::fake(function (Request $request, array $options) use (&$sent) {
            $sent = $options;

            return Http::response([]);
        });

        Http::withOptions(SafeWebhookUrl::httpOptions('https://8.8.8.8/hook'))->post('https://8.8.8.8/hook');

        return $sent;
    });

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen['curl'][CURLOPT_RESOLVE])->toBe(['8.8.8.8:443:8.8.8.8']);
});

it('turns redirects off even when the provider uses its default url', function (): void {
    $seen = ScopedProviderConfig::run('openai', ['key' => 'platform-key'], fn (): array => sentHttpOptions());

    expect($seen['allow_redirects'])->toBeFalse()
        ->and($seen)->not->toHaveKey('curl');
});

it('refuses a platform url that cannot be validated and pinned, before changing any config', function (string $url): void {
    config(['ai.providers.openai.key' => 'original-key']);

    $called = false;

    expect(fn () => ScopedProviderConfig::run('openai', ['key' => 'platform-key', 'url' => $url], function () use (&$called): void {
        $called = true;
    }))->toThrow(RuntimeException::class, trans('admin::app.configuration.platform.message.unsafe-api-url'));

    expect($called)->toBeFalse()
        ->and(config('ai.providers.openai.key'))->toBe('original-key');
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
