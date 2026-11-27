<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Render a Magic AI platform field template and return its HTML.
 */
function magicAiPlatformHintRender(string $field): string
{
    return view('admin::configuration.magic-ai.field.'.$field)->render();
}

dataset('magic ai platform fields', [
    'text generation'  => 'text-generation-platform',
    'image generation' => 'image-generation-platform',
    'translation'      => 'translation-platform',
]);

it('warns that no platform is configured when none is active', function (string $field): void {
    MagicAIPlatform::query()->update(['status' => false]);

    $html = magicAiPlatformHintRender($field);

    expect($html)->toContain(trans('admin::app.configuration.platform.setup.no-platform-hint'))
        ->not->toContain(e(trans('admin::app.configuration.platform.fields.use-default-hint')));
})->with('magic ai platform fields');

it('shows the use default hint instead of the warning when a platform is active', function (string $field): void {
    MagicAIPlatform::factory()->create();

    $html = magicAiPlatformHintRender($field);

    expect($html)->toContain(e(trans('admin::app.configuration.platform.fields.use-default-hint')))
        ->not->toContain(trans('admin::app.configuration.platform.setup.no-platform-hint'));
})->with('magic ai platform fields');

it('marks the default platform with an asterisk in every platform field', function (string $field): void {
    MagicAIPlatform::query()->update(['is_default' => false]);

    $platform = MagicAIPlatform::factory()->create(['label' => 'Hint Default Platform', 'is_default' => true]);

    expect(magicAiPlatformHintRender($field))->toContain(e(json_encode($platform->label.' (Openai) *')));
})->with('magic ai platform fields');
