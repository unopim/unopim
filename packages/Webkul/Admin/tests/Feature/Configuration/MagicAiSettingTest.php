<?php

it('should return the magic ai configuration page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.configuration.edit', ['general', 'magic_ai']))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.index.general.magic-ai.title'))
        ->assertSeeText(trans('admin::app.configuration.index.general.magic-ai.settings.title'));
});

it('should save and update the settings for magic ai', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.settings';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'      => '1',
        'api_key'      => 'test-saved-value',
        'organization' => 'org-9',
        'api_domain'   => 'demo.demo.com',
    ];

    $data['general']['magic_ai']['settings'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.settings.api_key',
        'value' => 'test-saved-value',
    ]);
});
