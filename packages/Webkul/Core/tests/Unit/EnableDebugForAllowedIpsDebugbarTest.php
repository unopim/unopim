<?php

use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Webkul\Core\Http\Middleware\EnableDebugForAllowedIps;
use Webkul\Core\Models\CoreConfig;

beforeEach(function () {
    CoreConfig::where('code', 'like', 'general.debug.settings.%')->delete();

    Cache::flush();
});

afterEach(fn () => app(LaravelDebugbar::class)->disable());

it('leaves a debugbar enabled by the environment alone when IP-based debug is off', function () {
    CoreConfig::create(['code' => 'general.debug.settings.enabled', 'value' => '0']);

    app(LaravelDebugbar::class)->enable();

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '10.0.0.5']),
        fn (Request $request) => response('ok')
    );

    expect(app(LaravelDebugbar::class)->isEnabled())->toBeTrue();
});

it('switches the debugbar off for a caller outside the allow-list when IP-based debug is on', function () {
    CoreConfig::create(['code' => 'general.debug.settings.enabled', 'value' => '1']);
    CoreConfig::create(['code' => 'general.debug.settings.allowed_ips', 'value' => '10.0.0.5']);

    app(LaravelDebugbar::class)->enable();

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '203.0.113.9']),
        fn (Request $request) => response('ok')
    );

    expect(app(LaravelDebugbar::class)->isEnabled())->toBeFalse();
});

it('hides the debug bar from callers outside APP_DEBUG_ALLOWED_IPS when IP-based debug is off', function () {
    CoreConfig::create(['code' => 'general.debug.settings.enabled', 'value' => '0']);

    config(['app.debug_allowed_ips' => '10.0.0.5']);

    app(LaravelDebugbar::class)->enable();

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '203.0.113.9']),
        fn (Request $request) => response('ok')
    );

    expect(app(LaravelDebugbar::class)->isEnabled())->toBeFalse()
        ->and(config('debugbar.enabled'))->toBeFalse();
});

it('keeps the debug bar for a caller on APP_DEBUG_ALLOWED_IPS when IP-based debug is off', function () {
    CoreConfig::create(['code' => 'general.debug.settings.enabled', 'value' => '0']);

    config(['app.debug_allowed_ips' => '10.0.0.5']);

    app(LaravelDebugbar::class)->enable();

    (new EnableDebugForAllowedIps)->handle(
        Request::create('/x', 'GET', server: ['REMOTE_ADDR' => '10.0.0.5']),
        fn (Request $request) => response('ok')
    );

    expect(app(LaravelDebugbar::class)->isEnabled())->toBeTrue();
});
