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
        'enabled' => '1',
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
        'code'  => 'general.magic_ai.settings.enabled',
        'value' => '1',
    ]);
});

it('should save and update the translation settings for magic ai', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.translation';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'        => '1',
        'source_channel' => 'default',
        'source_locale'  => 'af_ZA',
        'ai_platform'    => '0',
        'ai_model'       => '0',
    ];

    $data['general']['magic_ai']['translation'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $records = [
        ['code' => 'general.magic_ai.translation.enabled', 'value' => '1'],
        ['code' => 'general.magic_ai.translation.source_channel', 'value' => 'default'],
        ['code' => 'general.magic_ai.translation.source_locale', 'value' => 'af_ZA'],
    ];

    foreach ($records as $record) {
        $this->assertDatabaseHas('core_config', $record);
    }
});

it('should save and update the image generation settings for magic ai', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.image_generation';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled' => '1',
    ];

    $data['general']['magic_ai']['image_generation'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.image_generation.enabled',
        'value' => '1',
    ]);
});

it('should disable image generation for magic ai', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.image_generation';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled' => '0',
    ];

    $data['general']['magic_ai']['image_generation'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.image_generation.enabled',
        'value' => '0',
    ]);
});

it('should disable the magic ai settings', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.settings';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled' => '0',
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
        'code'  => 'general.magic_ai.settings.enabled',
        'value' => '0',
    ]);
});

it('should save and update the translation settings with all fields for magic ai', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.translation';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'        => '1',
        'replace'        => '1',
        'source_channel' => 'default',
        'target_channel' => 'default',
        'source_locale'  => 'en_US',
        'target_locale'  => 'fr_FR',
        'ai_platform'    => '0',
        'ai_model'       => '0',
    ];

    $data['general']['magic_ai']['translation'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $records = [
        ['code' => 'general.magic_ai.translation.enabled', 'value' => '1'],
        ['code' => 'general.magic_ai.translation.replace', 'value' => '1'],
        ['code' => 'general.magic_ai.translation.source_channel', 'value' => 'default'],
        ['code' => 'general.magic_ai.translation.target_channel', 'value' => 'default'],
        ['code' => 'general.magic_ai.translation.source_locale', 'value' => 'en_US'],
        ['code' => 'general.magic_ai.translation.target_locale', 'value' => 'fr_FR'],
    ];

    foreach ($records as $record) {
        $this->assertDatabaseHas('core_config', $record);
    }
});

it('should save and update the agentic pim settings for magic ai', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.agentic_pim';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'              => '1',
        'max_steps'            => '5',
        'daily_token_budget'   => '500000',
        'auto_enrichment'      => '1',
        'quality_monitor'      => '1',
        'confidence_threshold' => '0.7',
        'approval_mode'        => 'auto',
    ];

    $data['general']['magic_ai']['agentic_pim'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $records = [
        ['code' => 'general.magic_ai.agentic_pim.enabled', 'value' => '1'],
        ['code' => 'general.magic_ai.agentic_pim.max_steps', 'value' => '5'],
        ['code' => 'general.magic_ai.agentic_pim.daily_token_budget', 'value' => '500000'],
        ['code' => 'general.magic_ai.agentic_pim.auto_enrichment', 'value' => '1'],
        ['code' => 'general.magic_ai.agentic_pim.quality_monitor', 'value' => '1'],
        ['code' => 'general.magic_ai.agentic_pim.confidence_threshold', 'value' => '0.7'],
        ['code' => 'general.magic_ai.agentic_pim.approval_mode', 'value' => 'auto'],
    ];

    foreach ($records as $record) {
        $this->assertDatabaseHas('core_config', $record);
    }
});

it('should save agentic pim with strict confirm approval mode', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.agentic_pim';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'              => '1',
        'max_steps'            => '10',
        'daily_token_budget'   => '0',
        'auto_enrichment'      => '0',
        'quality_monitor'      => '0',
        'confidence_threshold' => '0.9',
        'approval_mode'        => 'review',
    ];

    $data['general']['magic_ai']['agentic_pim'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $records = [
        ['code' => 'general.magic_ai.agentic_pim.max_steps', 'value' => '10'],
        ['code' => 'general.magic_ai.agentic_pim.daily_token_budget', 'value' => '0'],
        ['code' => 'general.magic_ai.agentic_pim.confidence_threshold', 'value' => '0.9'],
        ['code' => 'general.magic_ai.agentic_pim.approval_mode', 'value' => 'review'],
    ];

    foreach ($records as $record) {
        $this->assertDatabaseHas('core_config', $record);
    }
});

it('should save agentic pim with suggest only approval mode', function () {
    $this->loginAsAdmin();

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.agentic_pim';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'              => '0',
        'max_steps'            => '3',
        'daily_token_budget'   => '100000',
        'auto_enrichment'      => '0',
        'quality_monitor'      => '1',
        'confidence_threshold' => '0.5',
        'approval_mode'        => 'suggest',
    ];

    $data['general']['magic_ai']['agentic_pim'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();

    $response->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $records = [
        ['code' => 'general.magic_ai.agentic_pim.enabled', 'value' => '0'],
        ['code' => 'general.magic_ai.agentic_pim.max_steps', 'value' => '3'],
        ['code' => 'general.magic_ai.agentic_pim.confidence_threshold', 'value' => '0.5'],
        ['code' => 'general.magic_ai.agentic_pim.approval_mode', 'value' => 'suggest'],
    ];

    foreach ($records as $record) {
        $this->assertDatabaseHas('core_config', $record);
    }
});
