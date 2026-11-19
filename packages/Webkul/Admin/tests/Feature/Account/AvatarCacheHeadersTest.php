<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Http::fake();
});

it('serves a found gravatar with a public cache header', function () {
    $hash = hash('sha256', 'cached-avatar@example.com');

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
    $hash = hash('sha256', 'no-avatar@example.com');

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

it('addresses gravatars by the sha256 of the email rather than its md5', function () {
    $url = Admin::getGravatarUrlFromEmail(' Mixed.Case@Example.com ');

    expect($url)->toBe(route('admin.avatar.public', ['hash' => hash('sha256', 'mixed.case@example.com')]));
    expect($url)->not->toContain(md5('mixed.case@example.com'));
});

it('rejects a legacy md5 avatar hash', function () {
    $this->get('/'.config('app.admin_url').'/avatar/u/'.md5('legacy@example.com'))->assertNotFound();
});
