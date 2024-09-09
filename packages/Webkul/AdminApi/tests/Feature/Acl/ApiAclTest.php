<?php

/**Locale Acl Test */
it('should not display the locale list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.index'))
        ->assertForbidden();
});

it('should not display the locale get route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.get', 'en_US'))
        ->assertForbidden();
});

it('should display the locale list route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.index'))
        ->assertOk();
});

it('should display the locale get route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.get', 'en_US'))
        ->assertOk();
});

/** Currency Acl Test */
it('should not display the currencies list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.index'))
        ->assertForbidden();
});

it('should not display the currency by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.get', 'USD'))
        ->assertForbidden();
});

it('should display the currencies list route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.currencies']);

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.index'))
        ->assertOk();
});

it('should display the currency get route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.currencies']);

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.get', 'USD'))
        ->assertOk();
});

/** Channel Acl Test */
it('should not display the channel list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.index'))
        ->assertForbidden();
});

it('should not display the channel by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.get', 'default'))
        ->assertForbidden();
});

it('should display the channels list route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.channels']);

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.index'))
        ->assertOk();
});

it('should display the channel by code route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.channels']);

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.get', 'default'))
        ->assertOk();
});
