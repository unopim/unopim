<?php

/*
 * The Debug page under the System Settings hub is the real save path for
 * general.debug.settings.allowed_ips — SystemSettingsController::update()
 * persists straight from the request with no validation at all, so a garbage
 * value like "34.65.hgjmgh.hjfghfgh" saved untouched.
 */
beforeEach(fn () => $this->loginAsAdmin());

it('rejects a garbage value for the allowed-ips field on the hub debug page', function () {
    $this->put(route('admin.settings.system.update', 'system.debug'), [
        'general' => ['debug' => ['settings' => ['allowed_ips' => '34.65.hgjmgh.hjfghfgh']]],
    ])->assertSessionHasErrors('general.debug.settings.allowed_ips');

    expect(core()->getConfigData('general.debug.settings.allowed_ips'))->toBeNull();
});

it('accepts a comma separated list of valid ipv4/ipv6 addresses on the hub debug page', function () {
    $this->put(route('admin.settings.system.update', 'system.debug'), [
        'general' => ['debug' => ['settings' => ['allowed_ips' => '192.168.1.1, ::1']]],
    ])->assertSessionDoesntHaveErrors();

    expect(core()->getConfigData('general.debug.settings.allowed_ips'))->toBe('192.168.1.1, ::1');
});

it('allows the hub debug field to stay empty', function () {
    $this->put(route('admin.settings.system.update', 'system.debug'), [
        'general' => ['debug' => ['settings' => ['allowed_ips' => '']]],
    ])->assertSessionDoesntHaveErrors();
});
