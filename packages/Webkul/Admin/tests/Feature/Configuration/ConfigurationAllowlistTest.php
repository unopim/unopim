<?php

/*
 * The legacy configuration store must persist only the config codes declared by
 * the group being edited. A crafted request carrying unrelated codes (SMTP creds,
 * AI keys, debug flags) must not be written to core_config.
 */
it('does not persist config codes outside the edited group', function () {
    $this->loginAsAdmin();

    config(['core' => [
        [
            'key'    => 'general.testgroup',
            'name'   => 'Test Group',
            'fields' => [['name' => 'allowed_field', 'title' => 'Allowed', 'type' => 'text']],
        ],
    ]]);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'testgroup']), [
        'general'  => ['testgroup' => ['allowed_field' => 'ok']],
        'injected' => ['evil' => ['code' => 'HACKED']],
    ]);

    expect(core()->getConfigData('general.testgroup.allowed_field'))->toBe('ok');

    $this->assertDatabaseMissing('core_config', ['code' => 'injected.evil.code']);
});

it('does not persist an undeclared field inside the edited group', function () {
    $this->loginAsAdmin();

    config(['core' => [
        [
            'key'    => 'general.testgroup',
            'name'   => 'Test Group',
            'fields' => [['name' => 'allowed_field', 'title' => 'Allowed', 'type' => 'text']],
        ],
    ]]);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'testgroup']), [
        'general' => ['testgroup' => ['allowed_field' => 'ok', 'secret_field' => 'HACKED']],
    ]);

    expect(core()->getConfigData('general.testgroup.allowed_field'))->toBe('ok');

    $this->assertDatabaseMissing('core_config', ['code' => 'general.testgroup.secret_field']);
});
