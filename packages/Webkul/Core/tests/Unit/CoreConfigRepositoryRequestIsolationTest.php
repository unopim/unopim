<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;

beforeEach(function () {
    config()->set('core', [
        [
            'key'    => 'test.first',
            'fields' => [['name' => 'value', 'type' => 'text']],
        ],
        [
            'key'    => 'test.second',
            'fields' => [['name' => 'value', 'type' => 'text']],
        ],
    ]);
});

it('flattens only the submitted group on each call', function () {
    $repository = app(CoreConfigRepository::class);

    expect($repository->recursiveArray(['first' => ['value' => 'one']], 'test'))
        ->toBe(['test.first.value' => 'one'])
        ->and($repository->recursiveArray(['second' => ['value' => 'two']], 'test'))
        ->toBe(['test.second.value' => 'two']);
});

it('does not write the values of an earlier save again on the next save', function () {
    $repository = app(CoreConfigRepository::class);

    $repository->create(['test' => ['first' => ['value' => 'one']]]);

    CoreConfig::query()->where('code', 'test.first.value')->delete();

    $repository->create(['test' => ['second' => ['value' => 'two']]]);

    $this->assertDatabaseHas('core_config', ['code' => 'test.second.value', 'value' => 'two']);
    $this->assertDatabaseMissing('core_config', ['code' => 'test.first.value']);
});
