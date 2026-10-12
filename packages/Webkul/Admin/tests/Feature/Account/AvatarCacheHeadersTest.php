<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

it('serves a found gravatar with a public cache header', function () {
    $hash = md5('cached-avatar@example.com');

    Cache::put("admin.gravatar.{$hash}", [
        'found'        => true,
        'body'         => 'binary-image-bytes',
        'content_type' => 'image/png',
        'fetched_at'   => now()->getTimestamp(),
    ], 600);

    $response = $this->get(route('admin.avatar.public', ['hash' => $hash]));

    $response->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('public');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=300');
});

it('serves a missing gravatar as a cacheable 404', function () {
    $hash = md5('no-avatar@example.com');

    Cache::put("admin.gravatar.{$hash}", [
        'found'        => false,
        'body'         => '',
        'content_type' => 'image/png',
        'fetched_at'   => now()->getTimestamp(),
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
