<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Exceptions;
use Webkul\AiAgent\Chat\ExtensionRegistry;
use Webkul\AiAgent\Http\Controllers\ChatController;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

it('uses a registered extension provider without looking up a database default', function () {
    $platform = new MagicAIPlatform(['provider' => 'example', 'status' => true]);
    app(ExtensionRegistry::class)->provider(-7, fn () => $platform, ['label' => 'Example']);
    $repository = Mockery::mock(MagicAIPlatformRepository::class);
    $repository->shouldNotReceive('find', 'getActiveDefault', 'getActiveList');
    app()->instance(MagicAIPlatformRepository::class, $repository);

    expect((new ReflectionMethod(ChatController::class, 'resolvePlatform'))->invoke(app(ChatController::class), -7))
        ->toBe($platform);
});

it('falls back consistently when the requested provider is unavailable', function (string $selection, string $fallback) {
    $id = match ($selection) {
        'unknown', 'unavailable' => -7,
        'missing', 'disabled'    => 7,
        default                  => 0,
    };
    if ($selection === 'unavailable') {
        app(ExtensionRegistry::class)->provider(-7, fn () => null, ['label' => 'Unavailable']);
    }
    $platform = $fallback === 'none' ? null : new MagicAIPlatform(['provider' => 'fallback', 'status' => true]);
    $repository = Mockery::mock(MagicAIPlatformRepository::class);
    if ($id > 0) {
        $repository->shouldReceive('find')->once()->with($id)
            ->andReturn($selection === 'disabled' ? new MagicAIPlatform(['status' => false]) : null);
    } else {
        $repository->shouldNotReceive('find');
    }
    $repository->shouldReceive('getActiveDefault')->once()->andReturn($fallback === 'default' ? $platform : null);
    if ($fallback === 'default') {
        $repository->shouldNotReceive('getActiveList');
    } else {
        $repository->shouldReceive('getActiveList')->once()->andReturn(new Collection($platform ? [$platform] : []));
    }
    app()->instance(MagicAIPlatformRepository::class, $repository);

    expect((new ReflectionMethod(ChatController::class, 'resolvePlatform'))->invoke(app(ChatController::class), $id))
        ->toBe($platform);
})->with(['unknown', 'unavailable', 'missing', 'disabled', 'default'])->with(['default', 'first', 'none']);

it('falls back to the default platform when an extension provider is disabled or throws', function (string $failure) {
    $default = new MagicAIPlatform(['provider' => 'fallback', 'status' => true]);
    app(ExtensionRegistry::class)->provider(-7, fn () => $failure === 'throws'
        ? throw new RuntimeException('Extension backend offline')
        : new MagicAIPlatform(['provider' => 'example', 'status' => false]), ['label' => 'Example']);
    $repository = Mockery::mock(MagicAIPlatformRepository::class);
    $repository->shouldNotReceive('find');
    $repository->shouldReceive('getActiveDefault')->once()->andReturn($default);
    app()->instance(MagicAIPlatformRepository::class, $repository);
    Exceptions::fake();

    expect((new ReflectionMethod(ChatController::class, 'resolvePlatform'))->invoke(app(ChatController::class), -7))
        ->toBe($default);

    $failure === 'throws'
        ? Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'Extension backend offline')
        : Exceptions::assertNothingReported();
})->with(['throws', 'disabled']);
