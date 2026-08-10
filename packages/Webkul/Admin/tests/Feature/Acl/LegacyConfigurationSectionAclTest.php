<?php

$restricted = ['dashboard', 'configuration', 'configuration.integrations'];

$emailPayload = ['emails' => ['configure' => ['email_settings' => ['mail_host' => 'evil.smtp.example']]]];

$debugPayload = ['general' => ['debug' => ['settings' => ['allowed_ips' => '203.0.113.5']]]];

$ssoPayload = ['general' => ['microsoft_sso' => ['settings' => ['tenant' => 'attacker-tenant']]]];

it('denies writing email settings through the legacy configuration route', function () use ($restricted, $emailPayload) {
    $this->loginWithPermissions(permissions: $restricted);

    $this->post(route('admin.configuration.store', ['slug' => 'emails', 'slug2' => 'configure']), $emailPayload)
        ->assertStatus(403);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'emails.configure.email_settings.mail_host',
        'value' => 'evil.smtp.example',
    ]);
});

it('denies writing debug settings through the legacy configuration route', function () use ($restricted, $debugPayload) {
    $this->loginWithPermissions(permissions: $restricted);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'debug']), $debugPayload)
        ->assertStatus(403);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.debug.settings.allowed_ips',
        'value' => '203.0.113.5',
    ]);
});

it('denies writing microsoft sso settings through the legacy configuration route', function () use ($restricted, $ssoPayload) {
    $this->loginWithPermissions(permissions: $restricted);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'microsoft_sso']), $ssoPayload)
        ->assertStatus(403);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.microsoft_sso.settings.tenant',
        'value' => 'attacker-tenant',
    ]);
});

it('denies the slug-less configuration post that targets every section at once', function () use ($restricted, $emailPayload, $debugPayload, $ssoPayload) {
    $this->loginWithPermissions(permissions: $restricted);

    $payload = array_merge_recursive($emailPayload, $debugPayload, $ssoPayload);

    $this->post(route('admin.configuration.store'), $payload)
        ->assertStatus(403);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'emails.configure.email_settings.mail_host',
        'value' => 'evil.smtp.example',
    ]);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.debug.settings.allowed_ips',
        'value' => '203.0.113.5',
    ]);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.microsoft_sso.settings.tenant',
        'value' => 'attacker-tenant',
    ]);
});

it('allows writing a section the role is explicitly granted', function () use ($emailPayload) {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'configuration',
        'configuration.system_settings',
        'configuration.system_settings.email',
    ]);

    $this->post(route('admin.configuration.store', ['slug' => 'emails', 'slug2' => 'configure']), $emailPayload)
        ->assertRedirect();

    $this->assertDatabaseHas('core_config', [
        'code'  => 'emails.configure.email_settings.mail_host',
        'value' => 'evil.smtp.example',
    ]);
});

it('isolates sections on the legacy route: the email grant does not unlock debug', function () use ($debugPayload) {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'configuration',
        'configuration.system_settings',
        'configuration.system_settings.email',
    ]);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'debug']), $debugPayload)
        ->assertStatus(403);

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.debug.settings.allowed_ips',
        'value' => '203.0.113.5',
    ]);
});

it('denies reading a hub-owned section through the legacy configuration route', function () use ($restricted) {
    $this->loginWithPermissions(permissions: $restricted);

    $this->get(route('admin.configuration.edit', ['slug' => 'emails', 'slug2' => 'configure']))
        ->assertStatus(403);
});

it('allows reading a hub-owned section with the matching permission', function () {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'configuration',
        'configuration.system_settings',
        'configuration.system_settings.email',
    ]);

    $this->get(route('admin.configuration.edit', ['slug' => 'emails', 'slug2' => 'configure']))
        ->assertOk();
});

it('keeps the magic ai settings page reachable for an ai-agent role', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'ai-agent.general']);

    $this->get(route('admin.magic_ai.settings.index'))
        ->assertOk();
});

it('keeps saving magic ai settings working, since no hub row owns that group', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'magic_ai']), [
        'general' => ['magic_ai' => ['settings' => ['enabled' => '1']]],
    ])->assertRedirect();

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.settings.enabled',
        'value' => '1',
    ]);
});
