<?php

use Webkul\Core\Models\Locale;

it('renders the system information page with runtime details', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.configuration.system.information'))
        ->assertOk()
        ->assertSee(trans('admin::app.help.system-info.title'))
        ->assertSee(PHP_VERSION)
        ->assertSee(app()->version())
        ->assertSee(core()->version())
        ->assertSee('framework');
});

it('renders the system information page in the requested locale', function () {
    $admin = $this->loginAsAdmin();
    $admin->update([
        'ui_locale_id' => Locale::query()->where('code', 'hi_IN')->value('id'),
    ]);

    $this->get(route('admin.configuration.system.information'))
        ->assertOk()
        ->assertSeeText('सिस्टम जानकारी')
        ->assertSeeText('एप्लिकेशन')
        ->assertSeeText('ऑपरेटिंग सिस्टम')
        ->assertDontSeeText('System Information')
        ->assertDontSeeText('Operating System');
});

it('denies the system information page without permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->get(route('admin.configuration.system.information'))->assertForbidden();
});
