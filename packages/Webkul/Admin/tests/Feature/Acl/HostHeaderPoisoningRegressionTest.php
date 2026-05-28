<?php

use Illuminate\Support\Facades\URL;

/**
 * Regression cover for Host / X-Forwarded-Host header poisoning. Laravel's
 * url() / asset() / Vite helpers must always resolve against APP_URL —
 * never against the request's Host header — otherwise an attacker can
 * cause the admin layout to load JavaScript from an attacker-controlled
 * origin (frontend takeover).
 */
beforeEach(function () {
    config()->set('app.url', 'http://canonical.test');

    URL::forceRootUrl(config('app.url'));
});

it('asset() helper resolves against APP_URL not request Host', function () {
    $this->withServerVariables([
        'HTTP_HOST'             => 'evil.example.com',
        'HTTP_X_FORWARDED_HOST' => 'evil.example.com:9000',
    ]);

    expect(asset('foo.js'))->toStartWith('http://canonical.test/');
});

it('url() helper resolves against APP_URL not request Host', function () {
    $this->withServerVariables([
        'HTTP_HOST'             => 'evil.example.com',
        'HTTP_X_FORWARDED_HOST' => 'evil.example.com:9000',
    ]);

    expect(url('/admin'))->toStartWith('http://canonical.test/');
});

it('asset() and url() ignore X-Forwarded-Proto override', function () {
    $this->withServerVariables([
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST'  => 'evil.example.com',
    ]);

    expect(asset('foo.js'))->toStartWith('http://canonical.test/');
    expect(url('/admin'))->toStartWith('http://canonical.test/');
});
