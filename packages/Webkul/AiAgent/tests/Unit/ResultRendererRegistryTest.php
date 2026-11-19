<?php

use Webkul\AiAgent\Chat\ResultRendererRegistry;

it('keeps unknown result references inert and rejects executable descriptor fields', function () {
    $registry = app(ResultRendererRegistry::class);
    $reference = ['type' => 'uninstalled.lookup', 'id' => 'result_42'];
    expect($registry->descriptor($reference))->toBe($reference)
        ->and($registry->components())->toBe([])
        ->and($registry->descriptor($reference + ['component' => 'v-attacker']))->toBeNull()
        ->and($registry->descriptor(['type' => "lookup\n", 'id' => 'result']))->toBeNull()
        ->and($registry->descriptor(['type' => 'lookup', 'id' => '../other-user']))->toBeNull()
        ->and($registry->descriptor(['type' => 'lookup', 'id' => str_repeat('x', 65)]))->toBeNull();
});

it('maps only registered renderer views and composer consumers', function () {
    $registry = app(ResultRendererRegistry::class);
    $registry->register('example.lookup', 'v-example-result', 'example::result', 'example.catalog');
    expect($registry->components())->toBe(['example.lookup' => 'v-example-result'])
        ->and($registry->views())->toBe(['example::result'])
        ->and($registry->contextConsumers())->toBe(['example.lookup' => 'example.catalog']);
    expect(fn () => $registry->register('bad', 'script', 'example::result'))->toThrow(InvalidArgumentException::class);
});
