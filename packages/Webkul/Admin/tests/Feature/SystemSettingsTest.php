<?php

use Illuminate\Support\Facades\Route;

it('renders the combined system settings page with appearance, smtp and debug sections', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.configuration.system.settings'))
        ->assertOk()
        ->assertSee(trans('admin::app.components.layouts.sidebar.system-settings'))
        ->assertSee(trans('admin::app.settings.appearance.title'))
        ->assertSee(trans('admin::app.configuration.index.emails.configure.email-settings.title'))
        ->assertSee(trans('admin::app.configuration.index.general.debug.settings.title'));
});

it('renders logo and favicon previews with contained object-fit and constrained sizes', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.configuration.system.settings'))->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('object-fit="contain"');
    expect($content)->toContain('name="logo_image"');
    expect($content)->toContain('name="favicon"');
    expect($content)->not->toContain('height="180px"');
});

it('denies the system settings page without permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->get(route('admin.configuration.system.settings'))->assertForbidden();
});

it('no longer exposes the old system smtp and debug routes', function () {
    expect(Route::has('admin.system.smtp'))->toBeFalse();
    expect(Route::has('admin.system.debug'))->toBeFalse();
});
