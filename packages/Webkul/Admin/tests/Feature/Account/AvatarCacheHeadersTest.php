<?php

use Illuminate\Support\Facades\Cache;

/**
 * The users listing renders one avatar per row, so the browser asks this
 * endpoint for every user on every page change. `NoCacheMiddleware` used to
 * overwrite the controller's `Cache-Control` on every response, and a missing
 * gravatar answered with a bare uncached 404 — between them the browser
 * re-fetched every avatar on each page transition instead of using its cache.
 */
it('serves a found gravatar with a public cache header', function () {
    $hash = md5('cached-avatar@example.com');

    Cache::put("admin.gravatar.{$hash}", [
        'found'        => true,
        'body'         => 'binary-image-bytes',
        'content_type' => 'image/png',
    ], 600);

    $response = $this->get(route('admin.avatar.public', ['hash' => $hash]));

    $response->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('public');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=86400');
});

it('serves a missing gravatar as a cacheable 404', function () {
    $hash = md5('no-avatar@example.com');

    Cache::put("admin.gravatar.{$hash}", [
        'found'        => false,
        'body'         => '',
        'content_type' => 'image/png',
    ], 600);

    $response = $this->get(route('admin.avatar.public', ['hash' => $hash]));

    $response->assertStatus(404);

    expect($response->headers->get('Cache-Control'))->toContain('public');
    expect($response->headers->get('Cache-Control'))->not->toContain('no-store');
});

it('still prevents caching of admin pages', function () {
    $response = $this->get(route('admin.session.create'));

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});
