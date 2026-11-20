<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Cache::flush();
});

function gravatarCacheEntry(array $overrides = []): array
{
    return array_merge([
        'found'         => true,
        'body'          => 'old-bytes',
        'content_type'  => 'image/png',
        'last_modified' => 'Fri, 08 May 2026 14:58:59 GMT',
        'fetched_at'    => now()->getTimestamp(),
    ], $overrides);
}

describe('Gravatar revalidation', function () {
    it('does not contact gravatar while the cached payload is fresh', function () {
        $hash = md5('fresh@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry(), 86400);

        Http::fake();

        $response = $this->get(route('admin.avatar.public', ['hash' => $hash]));

        expect($response->getContent())->toBe('old-bytes');

        Http::assertNothingSent();
    });

    it('serves stale bytes immediately and revalidates conditionally in the background', function () {
        $hash = md5('stale@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry([
            'fetched_at' => now()->getTimestamp() - 400,
        ]), 86400);

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('new-bytes', 200, [
                'Content-Type'  => 'image/png',
                'Last-Modified' => 'Sat, 09 May 2026 10:00:00 GMT',
            ]),
        ]);

        $stale = $this->get(route('admin.avatar.public', ['hash' => $hash]));

        expect($stale->getContent())->toBe('old-bytes');

        Http::assertSent(fn ($request) => $request->header('If-Modified-Since') === ['Fri, 08 May 2026 14:58:59 GMT']);

        $fresh = $this->get(route('admin.avatar.public', ['hash' => $hash]));

        expect($fresh->getContent())->toBe('new-bytes');
        expect($fresh->headers->get('Last-Modified'))->toBe('Sat, 09 May 2026 10:00:00 GMT');
    });

    it('keeps the cached bytes when the upstream answers 304', function () {
        $hash = md5('unchanged@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry([
            'fetched_at' => now()->getTimestamp() - 400,
        ]), 86400);

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('', 304),
        ]);

        $this->get(route('admin.avatar.public', ['hash' => $hash]));

        $cached = Cache::get("admin.gravatar.{$hash}");

        expect($cached['body'])->toBe('old-bytes');
        expect($cached['found'])->toBeTrue();
        expect($cached['fetched_at'])->toBeGreaterThan(now()->getTimestamp() - 10);
    });

    it('revalidates a stale miss so a newly created gravatar surfaces', function () {
        $hash = md5('new-gravatar@example.com');

        Cache::put("admin.gravatar.{$hash}", [
            'found'        => false,
            'body'         => '',
            'content_type' => 'image/png',
            'fetched_at'   => now()->getTimestamp() - 400,
        ], 600);

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('img-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $this->get(route('admin.avatar.public', ['hash' => $hash]))->assertStatus(404);

        $this->get(route('admin.avatar.public', ['hash' => $hash]))->assertOk();
    });

    it('revalidates a legacy cache entry that predates freshness tracking', function () {
        $hash = md5('legacy@example.com');

        Cache::put("admin.gravatar.{$hash}", [
            'found'        => true,
            'body'         => 'old-bytes',
            'content_type' => 'image/png',
        ], 86400);

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('new-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $this->get(route('admin.avatar.public', ['hash' => $hash]));

        expect(Cache::get("admin.gravatar.{$hash}")['body'])->toBe('new-bytes');
    });

    it('refreshes a given hash at most once per freshness window', function () {
        $hash = md5('throttled@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry([
            'fetched_at' => now()->getTimestamp() - 400,
        ]), 86400);

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('', 304),
        ]);

        $this->get(route('admin.avatar.public', ['hash' => $hash]));
        $this->get(route('admin.avatar.public', ['hash' => $hash]));

        Http::assertSentCount(1);
    });
});

describe('Gravatar proxy cache headers', function () {
    it('sends a short revalidating cache header and the upstream last-modified date', function () {
        $hash = md5('headers@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry(), 86400);

        Http::fake();

        $response = $this->get(route('admin.avatar.public', ['hash' => $hash]));

        expect($response->headers->get('Cache-Control'))->toContain('public');
        expect($response->headers->get('Cache-Control'))->toContain('max-age=300');
        expect($response->headers->get('Cache-Control'))->toContain('must-revalidate');
        expect($response->headers->get('Last-Modified'))->toBe('Fri, 08 May 2026 14:58:59 GMT');
    });

    it('answers 304 when the browser revalidates an unchanged avatar', function () {
        $hash = md5('conditional@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry(), 86400);

        Http::fake();

        $response = $this->get(
            route('admin.avatar.public', ['hash' => $hash]),
            ['If-Modified-Since' => 'Fri, 08 May 2026 14:58:59 GMT'],
        );

        $response->assertStatus(304);

        expect($response->getContent())->toBe('');
    });

    it('sends the bytes when the browser holds an older copy', function () {
        $hash = md5('outdated-copy@example.com');

        Cache::put("admin.gravatar.{$hash}", gravatarCacheEntry(), 86400);

        Http::fake();

        $response = $this->get(
            route('admin.avatar.public', ['hash' => $hash]),
            ['If-Modified-Since' => 'Mon, 04 May 2026 10:00:00 GMT'],
        );

        $response->assertOk();

        expect($response->getContent())->toBe('old-bytes');
    });
});

describe('Gravatar cache invalidation on admin writes', function () {
    it('forgets the cached gravatar when the admin email changes', function () {
        $admin = Admin::factory()->create(['email' => 'before@example.com']);

        Cache::put('admin.gravatar.'.md5('before@example.com'), gravatarCacheEntry(), 86400);

        $admin->update(['email' => 'after@example.com']);

        expect(Cache::has('admin.gravatar.'.md5('before@example.com')))->toBeFalse();
    });

    it('forgets the cached gravatar when the gravatar toggle changes', function () {
        $admin = Admin::factory()->create([
            'email'        => 'toggled@example.com',
            'use_gravatar' => false,
        ]);

        Cache::put('admin.gravatar.'.md5('toggled@example.com'), gravatarCacheEntry(), 86400);

        $admin->update(['use_gravatar' => true]);

        expect(Cache::has('admin.gravatar.'.md5('toggled@example.com')))->toBeFalse();
    });

    it('keeps the cached gravatar when an unrelated field changes', function () {
        $admin = Admin::factory()->create(['email' => 'stable@example.com']);

        Cache::put('admin.gravatar.'.md5('stable@example.com'), gravatarCacheEntry(), 86400);

        $admin->update(['name' => 'Renamed Admin']);

        expect(Cache::has('admin.gravatar.'.md5('stable@example.com')))->toBeTrue();
    });
});
