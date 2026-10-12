<?php

use Webkul\Core\Repositories\CoreConfigRepository;

beforeEach(function () {
    config()->set('core', [
        'test_section' => [
            'key'    => 'test.section',
            'fields' => [
                ['name' => 'cleared_field', 'type' => 'text'],
                ['name' => 'defaulted_field', 'type' => 'text', 'default_value' => 'fallback'],
            ],
        ],
    ]);
});

it('persists an empty value when a core-config field is submitted as null', function () {
    app(CoreConfigRepository::class)->create([
        'test' => ['section' => ['cleared_field' => null]],
    ]);

    $this->assertDatabaseHas('core_config', [
        'code'  => 'test.section.cleared_field',
        'value' => '',
    ]);
});

it('falls back to the field default_value when submitted value is null', function () {
    app(CoreConfigRepository::class)->create([
        'test' => ['section' => ['defaulted_field' => null]],
    ]);

    $this->assertDatabaseHas('core_config', [
        'code'  => 'test.section.defaulted_field',
        'value' => 'fallback',
    ]);
});
