<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webkul\User\Models\Admin;

$gridRequest = fn ($test) => $test
    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
    ->get(route('admin.settings.users.index', ['pagination' => ['page' => 1, 'per_page' => 50]]));

it('serves a thumbnail rather than the original upload for an avatar', function () use ($gridRequest) {
    $this->loginAsAdmin();

    $user = Admin::factory()->create(['image' => 'admins/9/avatar.jpg']);

    $records = collect($gridRequest($this)->assertOk()->json('records'));

    $row = $records->firstWhere('user_id', $user->id);

    expect($row['user_img'])
        ->toContain('cache/small/admins/9/avatar.jpg')
        ->not->toContain('/storage/admins/9/avatar.jpg');
});

it('emits no avatar url at all for a user with no upload and no cached gravatar', function () use ($gridRequest) {
    $this->loginAsAdmin();

    Cache::flush();

    $user = Admin::factory()->create(['image' => null]);

    $records = collect($gridRequest($this)->assertOk()->json('records'));

    $row = $records->firstWhere('user_id', $user->id);

    expect($row['user_img'])->toBeNull();
});

it('never calls gravatar while rendering the users listing', function () use ($gridRequest) {
    $this->loginAsAdmin();

    Cache::flush();

    Http::fake();

    Admin::factory()->count(5)->create(['image' => null]);

    $gridRequest($this)->assertOk();

    Http::assertNothingSent();
});

it('uses the cached gravatar url once the lookup is known to have succeeded', function () use ($gridRequest) {
    $this->loginAsAdmin();

    $user = Admin::factory()->create(['image' => null]);

    Cache::put(
        'admin.gravatar.'.md5(mb_strtolower(trim($user->email))),
        ['found' => true, 'body' => '', 'content_type' => 'image/png'],
        86400
    );

    Http::fake();

    $records = collect($gridRequest($this)->assertOk()->json('records'));

    $row = $records->firstWhere('user_id', $user->id);

    expect($row['user_img'])->not->toBeNull();

    Http::assertNothingSent();
});

it('answers the cached gravatar check without contacting gravatar', function () {
    Cache::flush();

    Http::fake();

    expect(Admin::gravatarCachedForEmail('nobody@example.test'))->toBeFalse();

    Http::assertNothingSent();
});

it('renders the avatar image lazily and with intrinsic dimensions', function () {
    $this->loginAsAdmin();

    $content = $this->get(route('admin.settings.users.index'))->assertOk()->getContent();

    expect($content)
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"')
        ->toContain('width="36"')
        ->toContain('height="36"');
});
