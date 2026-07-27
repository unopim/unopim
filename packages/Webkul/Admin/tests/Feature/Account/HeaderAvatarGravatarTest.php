<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Cache::flush();
});

describe('Gravatar avatar fallback when no image is uploaded', function () {
    it('avatar proxy route has no static-file extension so nginx forwards it to php', function () {
        $url = route('admin.avatar.public', ['hash' => str_repeat('a', 32)]);

        expect($url)->not->toEndWith('.png');
    });

    it('reports a gravatar exists only when the upstream lookup succeeds', function () {
        $hasHash = md5('has-gravatar@example.com');
        $noHash = md5('no-gravatar@example.com');

        Http::fake([
            "gravatar.com/avatar/{$hasHash}*" => Http::response('img-bytes', 200, ['Content-Type' => 'image/png']),
            "gravatar.com/avatar/{$noHash}*"  => Http::response('', 404),
        ]);

        expect(Admin::gravatarExistsForEmail('has-gravatar@example.com'))->toBeTrue();
        expect(Admin::gravatarExistsForEmail('no-gravatar@example.com'))->toBeFalse();
        expect(Admin::gravatarExistsForEmail(null))->toBeFalse();
    });

    it('renders the gravatar avatar in the header when the email has one and no image is uploaded', function () {
        Http::fake([
            'gravatar.com/avatar/*' => Http::response('img-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $admin = Admin::factory()->create([
            'email' => 'gravatar-user@example.com',
            'image' => null,
        ]);

        $this->loginAsAdmin($admin);

        $expected = route('admin.avatar.public', ['hash' => md5('gravatar-user@example.com')]);

        $response = $this->get(route('admin.account.edit'));

        $response->assertStatus(200);
        $response->assertSee($expected, false);
    });

    it('serves the cached gravatar bytes through the proxy route', function () {
        $hash = md5('gravatar-user@example.com');

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('img-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->get(route('admin.avatar.public', ['hash' => $hash]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        expect($response->streamedContent() ?: $response->getContent())->toBe('img-bytes');
    });

    it('returns 404 through the proxy route when the email has no gravatar', function () {
        $hash = md5('no-gravatar@example.com');

        Http::fake([
            "gravatar.com/avatar/{$hash}*" => Http::response('', 404),
        ]);

        $this->get(route('admin.avatar.public', ['hash' => $hash]))->assertStatus(404);
    });
});
