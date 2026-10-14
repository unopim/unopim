<?php

use Laravel\Ai\Contracts\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\AuthorizesContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Chat\ExtensionRegistry;
use Webkul\AiAgent\Chat\ResultRendererRegistry;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('resolves extension providers lazily and keeps the registered negative identity', function () {
    $registry = app(ExtensionRegistry::class);
    $calls = 0;
    $registry->provider(-7, function () use (&$calls) {
        $calls++;

        return new MagicAIPlatform(['provider' => 'example']);
    }, ['id' => 42, 'label' => 'Example']);
    expect($registry->options()[0]['id'])->toBe(-7)->and($calls)->toBe(0);
    expect($registry->resolve(-7)->provider)->toBe('example')->and($calls)->toBe(1);
    expect($registry->resolve(-8))->toBeNull();
    expect(fn () => $registry->provider(1, fn () => null, []))->toThrow(InvalidArgumentException::class);
});

it('rejects composer names that could escape their namespace or markup', function (string $key, string $component) {
    expect(fn () => app(ExtensionRegistry::class)->composer($key, $component, 'example::picker'))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['bad\'key', 'v-picker'], ['ok', 'div onclick=alert(1)'], ['ok', 'V-Picker'],
    ['ok', 'v-'], [str_repeat('x', 65), 'v-picker'], ["key\n", 'v-picker'], ['ok', "v-picker\n"],
]);

it('registers trusted composers and replaces the same namespace deterministically', function () {
    $registry = app(ExtensionRegistry::class);
    $registry->composer('example.catalog', 'v-example-picker', 'example::picker');
    $registry->composer('example.catalog', 'v-new-picker', 'example::new');
    expect($registry->composers())->toHaveCount(1)
        ->and($registry->composers()[0]['component'])->toBe('v-new-picker');
});

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

it('runs decorators in order with fresh request context only after authorization', function (bool $acl, bool $authorized) {
    $registry = new ToolRegistry;
    $tool = Mockery::mock(PimTool::class.', '.AuthorizesContext::class);
    $tool->shouldReceive('authorize')->times($acl ? 2 : 0)->andReturn($authorized);
    $native = Mockery::mock(Tool::class);
    $tool->shouldReceive('register')->times($acl && $authorized ? 2 : 0)->andReturn($native);
    $registry->register($tool, ['name' => 'example', 'permission' => 'catalog.products']);
    $seen = [];
    foreach ([1, 2] as $position) {
        $registry->decorate(function ($registered, $context, $metadata) use (&$seen, $position, $native) {
            expect($registered)->toBe($native)->and($metadata['name'])->toBe('example');
            $seen[] = [$position, $context->message];

            return $registered;
        });
    }
    $admin = new Admin;
    $admin->setRelation('role', (new Role)->forceFill(['permission_type' => $acl ? 'all' : 'custom', 'permissions' => []]));
    foreach (['first request', 'second request'] as $message) {
        $context = new ChatContext($message, [], null, null, null, 'en_US', 'default', new MagicAIPlatform, user: $admin);
        expect($registry->build($context))->toHaveCount($acl && $authorized ? 1 : 0);
    }
    expect($seen)->toBe($acl && $authorized ? [[1, 'first request'], [2, 'first request'], [1, 'second request'], [2, 'second request']] : []);
})->with([[true, true], [false, true], [true, false]]);
