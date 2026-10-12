<?php

use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Middleware\TrustProxies;

it('trusts the configured hosts alongside the application url', function () {
    config(['app.trusted_hosts' => 'pim.example.test, cdn.example.test,,pim.example.test']);

    expect(app(TrustHosts::class)->hosts())->toBe([
        'pim.example.test',
        'cdn.example.test',
        '^(.+\.)?'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST)).'$',
    ]);
});

/*
 * The middleware configuration callback runs before the environment and the
 * config files are loaded, so a proxy list read there silently degrades to the
 * fallback — this asserts the resolved value still matches configuration.
 */
it('resolves the trusted proxies from configuration', function () {
    $proxies = array_values(array_unique(array_filter(
        array_map(trim(...), explode(',', (string) config('app.trusted_proxies')))
    )));

    $expected = in_array('*', $proxies, true) ? '*' : $proxies;

    expect((new ReflectionProperty(TrustProxies::class, 'alwaysTrustProxies'))->getValue())->toBe($expected);
});
