<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Webkul\Core\Http\Middleware\EnableDebugForAllowedIps;
use Webkul\Core\Models\CoreConfig;

function seedDebug(string $enabled, string $ips): void
{
    // Remove any rows carried over in the shared test database so the seeded
    // values are the ones the repository resolves.
    CoreConfig::where('code', 'like', 'general.debug.settings.%')->delete();

    CoreConfig::create(['code' => 'general.debug.settings.enabled', 'value' => $enabled]);
    CoreConfig::create(['code' => 'general.debug.settings.allowed_ips', 'value' => $ips]);

    Cache::flush();
}

it('registers the debug settings section in the configuration tree', function () {
    $section = collect(config('core'))->firstWhere('key', 'general.debug.settings');

    expect($section)->not->toBeNull();

    $fields = collect($section['fields'])->pluck('name');

    expect($fields)->toContain('enabled')->and($fields)->toContain('allowed_ips');
});

it('enables debug for an allow-listed IP when IP-based debug is on', function () {
    seedDebug('1', '10.0.0.5, 127.0.0.1');

    config(['app.debug' => false]);

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '10.0.0.5']),
        fn ($request) => response('ok')
    );

    expect(config('app.debug'))->toBeTrue();
});

it('does not enable debug for a non allow-listed IP', function () {
    seedDebug('1', '10.0.0.5');

    config(['app.debug' => false]);
    config(['debugbar.enabled' => true]);

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '203.0.113.9']),
        fn ($request) => response('ok')
    );

    expect(config('app.debug'))->toBeFalse()
        ->and(config('debugbar.enabled'))->toBeFalse();
});

it('does not enable debug when the feature is disabled', function () {
    seedDebug('0', '10.0.0.5');

    config(['app.debug' => false]);

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '10.0.0.5']),
        fn ($request) => response('ok')
    );

    expect(config('app.debug'))->toBeFalse();
});

it('enables debug using forwarded client IP when request comes through loopback proxy', function () {
    seedDebug('1', '198.51.100.25');

    config(['app.debug' => false]);

    $request = Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1']);
    $request->headers->set('X-Forwarded-For', '198.51.100.25');

    (new EnableDebugForAllowedIps)->handle(
        $request,
        fn ($request) => response('ok')
    );

    expect(config('app.debug'))->toBeTrue();
});
