<?php

use Illuminate\Support\Facades\Cache;
use Webkul\User\Models\AdminPromoDismissal;

it('records a dismissal for the authenticated admin', function () {
    $admin = $this->loginAsAdmin();

    $response = $this->postJson(route('admin.help.promo.dismiss'), [
        'banner' => 'cloud',
    ]);

    $response->assertStatus(200);

    expect(
        AdminPromoDismissal::where('admin_id', $admin->id)
            ->where('banner', 'cloud')
            ->exists()
    )->toBeTrue();
});

it('rejects a guest dismiss request', function () {
    $response = $this->postJson(route('admin.help.promo.dismiss'), [
        'banner' => 'cloud',
    ]);

    expect($response->getStatusCode())->not->toBe(200);
});

it('rejects an invalid banner key', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.help.promo.dismiss'), [
        'banner' => 'foo',
    ])->assertStatus(422);
});

it('renders both banner tags on an admin page when outdated', function () {
    $this->loginAsAdmin();
    Cache::put('unopim_latest_version', '9.9.9', now()->addHour());

    $response = $this->get(route('admin.help.index'));

    $response->assertStatus(200);
    $response->assertSee(trans('admin::app.help.banners.cloud.tag'), false);
    $response->assertSee(trans('admin::app.help.banners.upgrade.tag'), false);
});

it('hides the cloud banner after it is dismissed', function () {
    $admin = $this->loginAsAdmin();
    Cache::put('unopim_latest_version', '9.9.9', now()->addHour());

    $this->postJson(route('admin.help.promo.dismiss'), [
        'banner' => 'cloud',
    ])->assertStatus(200);

    $response = $this->get(route('admin.help.index'));

    $response->assertStatus(200);
    // The banner tag also appears as a help-card title, so assert on the
    // banner message which is unique to the promo bar's serialized payload.
    $response->assertDontSee(trans('admin::app.help.banners.cloud.message'), false);
});
