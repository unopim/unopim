<?php

use Illuminate\Support\Facades\URL;

/**
 * Regression cover for Host / X-Forwarded-Host header poisoning. Laravel's
 * url() / asset() / Vite helpers must always resolve against APP_URL —
 * never against the request's Host header — otherwise an attacker can
 * cause the admin layout to load JavaScript from an attacker-controlled
 * origin (frontend takeover).
 */
it('renders base-url meta tag from APP_URL even when X-Forwarded-Host is spoofed', function () {
    config()->set('app.url', 'http://canonical.test');

    URL::forceRootUrl(config('app.url'));

    $response = $this->withHeaders([
        'X-Forwarded-Host'  => 'evil.example.com:9000',
        'X-Forwarded-Proto' => 'http',
    ])->get(route('admin.session.create'));

    $response->assertOk();
    $response->assertSee('<meta name="base-url" content="http://canonical.test">', false);
    $response->assertDontSee('evil.example.com', false);
});

it('renders base-url meta tag from APP_URL even when Host header is spoofed', function () {
    config()->set('app.url', 'http://canonical.test');

    URL::forceRootUrl(config('app.url'));

    $response = $this->withHeaders([
        'Host' => 'evil.example.com',
    ])->get(route('admin.session.create'));

    $response->assertOk();
    $response->assertSee('<meta name="base-url" content="http://canonical.test">', false);
    $response->assertDontSee('http://evil.example.com', false);
});

it('asset() helper resolves against APP_URL not request Host', function () {
    config()->set('app.url', 'http://canonical.test');

    URL::forceRootUrl(config('app.url'));

    $this->withServerVariables([
        'HTTP_HOST'             => 'evil.example.com',
        'HTTP_X_FORWARDED_HOST' => 'evil.example.com:9000',
    ]);

    expect(asset('foo.js'))->toStartWith('http://canonical.test/');
    expect(url('/foo'))->toStartWith('http://canonical.test/');
});
