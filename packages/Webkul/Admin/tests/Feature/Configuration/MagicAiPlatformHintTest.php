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

it('keeps the config field name, id and change event of each platform field', function (string $field, string $section): void {
    $html = magicAiPlatformHintRender($field);

    expect($html)->toContain('name="general[magic_ai]['.$section.'][ai_platform]"')
        ->toContain('id="general_magic_ai_'.$section.'_ai_platform"')
        ->toContain("magic-ai-platform-changed:general_magic_ai_{$section}_ai_platform");
})->with([
    'text generation'  => ['text-generation-platform', 'settings'],
    'image generation' => ['image-generation-platform', 'image_generation'],
    'translation'      => ['translation-platform', 'translation'],
]);
