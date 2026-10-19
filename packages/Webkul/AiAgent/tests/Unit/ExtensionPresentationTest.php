<?php

use Webkul\AiAgent\Chat\ExtensionRegistry;

it('keeps the registered negative provider ID in the widget options', function () {
    $registry = app(ExtensionRegistry::class);
    $registry->provider(-7, fn () => null, ['id' => 42, 'label' => 'Example', 'models' => ['example']]);

    expect($registry->options())->toBe([['id' => -7, 'label' => 'Example', 'models' => ['example']]]);
});

it('rejects invalid capability keys', function (string $key) {
    expect(fn () => app(ExtensionRegistry::class)->capability($key, ['description' => 'Example']))
        ->toThrow(InvalidArgumentException::class);
})->with(['', 'Upper', '1first', 'space key', "key\n", "bad'key", str_repeat('x', 65)]);

it('replaces a capability presentation under the same valid key', function (string $key) {
    $registry = app(ExtensionRegistry::class);
    $registry->capability($key, ['description' => 'First']);
    $registry->capability($key, ['description' => 'Updated', 'extension_event' => null]);

    expect($registry->capabilities())->toBe([$key => ['description' => 'Updated', 'extension_event' => null]]);
})->with(['edit_image', 'example.catalog-edit', str_repeat('x', 64)]);

it('stores only the documented presentation overrides', function () {
    $registry = app(ExtensionRegistry::class);
    $registry->capability('edit_image', [
        'description'        => 'Updated',
        'extension_event'    => 'example:open',
        'key'                => 'hijacked',
        'prompt'             => 'Injected prompt',
        'acceptsSpreadsheet' => true,
    ]);

    expect($registry->capabilities())->toBe(['edit_image' => ['description' => 'Updated', 'extension_event' => 'example:open']]);
});
