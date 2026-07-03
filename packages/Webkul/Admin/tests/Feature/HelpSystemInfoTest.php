<?php

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

it('denies the system information page without permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->get(route('admin.configuration.system.information'))->assertForbidden();
});
