<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Traits\ResolvesLandingUrl;

it('lands a role granted export execute with its parent keys on the exports page', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['data_transfer', 'data_transfer.export', 'data_transfer.export.execute']);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.settings.data_transfer.exports.index'));

    $this->assertAuthenticatedAs($admin, 'admin');

    $this->get(route('admin.settings.data_transfer.exports.index'))->assertOk();
});

it('skips acl routes that need parameters and fails closed when nothing else is reachable', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['data_transfer.export.execute']);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('admin.session.create'))
        ->assertSessionHas('error', trans('admin::app.errors.403.message'));

    $this->assertGuest('admin');
});

it('returns the login page as the ajax redirect url when only parameterised routes are permitted', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['data_transfer.export.execute']);

    $this->postJson(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJson(['redirect_url' => route('admin.session.create')]);

    $this->assertGuest('admin');
});

it('lands a role holding a parameterless acl route on that route', function () {
    $admin = $this->createAdminWithPermissions(permissions: ['data_transfer.export']);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.settings.data_transfer.exports.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('treats only get routes without required uri or domain parameters as landable', function () {
    Route::get('landing-probe/{id}', fn () => '')->name('landing-probe.required');
    Route::get('landing-probe-optional/{id?}', fn () => '')->name('landing-probe.optional');
    Route::get('landing-probe-domain', fn () => '')->domain('{tenant}.example.test')->name('landing-probe.domain');
    Route::post('landing-probe-post', fn () => '')->name('landing-probe.post');
    Route::getRoutes()->refreshNameLookups();

    $resolver = new class
    {
        use ResolvesLandingUrl;

        public function landable(string $name): bool
        {
            return $this->isLandableRoute($name);
        }
    };

    expect($resolver->landable('landing-probe.required'))->toBeFalse()
        ->and($resolver->landable('landing-probe.optional'))->toBeTrue()
        ->and($resolver->landable('landing-probe.domain'))->toBeFalse()
        ->and($resolver->landable('landing-probe.post'))->toBeFalse()
        ->and($resolver->landable('missing.route'))->toBeFalse()
        ->and($resolver->landable('admin.settings.data_transfer.exports.index'))->toBeTrue();
});
