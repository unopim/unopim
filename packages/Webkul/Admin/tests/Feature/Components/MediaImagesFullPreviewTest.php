<?php

use Illuminate\Support\Facades\Blade;

$imagesView = __DIR__.'/../../../src/Resources/views/components/media/image.blade.php';
$viewerView = __DIR__.'/../../../src/Resources/views/components/media/image-viewer.blade.php';
$enLang = __DIR__.'/../../../src/Resources/lang/en_US/app.php';

/*
 * `v-media-image` gains an opt-in full-size preview that rides the shared modal's
 * noClass mode. `fullPreview` is a reactive Vue prop threaded parent -> item so it
 * survives the @pushOnce template and can vary per field. Default stays the boxed
 * 260px preview, so every existing consumer is untouched.
 */

it('exposes fullPreview as a reactive prop, defaulted on', function () use ($imagesView) {
    $source = file_get_contents($imagesView);

    expect($source)->toContain("'fullPreview'      => true,")
        ->and($source)->toContain("v-bind:full-preview=\"{{ \$fullPreview ? 'true' : 'false' }}\"")
        ->and($source)->toContain(':fullPreview="fullPreview"')
        ->and($source)->toContain("'objectFit', 'responsive', 'fullPreview']");
});

it('keeps the boxed preview modal available as the compact opt-out', function () use ($imagesView) {
    $source = file_get_contents($imagesView);

    expect($source)->toContain('<x-admin::modal ref="imagePreviewModal">')
        ->and($source)->toContain('height: 260px;')
        ->and($source)->toContain('object-contain object-top');
});

it('adds a separate full-size modal using the shared viewer', function () use ($imagesView) {
    $source = file_get_contents($imagesView);

    expect($source)->toContain('<x-admin::modal ref="imagePreviewModalFull" no-class="true">')
        ->and($source)->toContain('<v-image-viewer');
});

it('registers the viewer outside the Vue template and uses a raw Vue tag inside it', function () use ($imagesView) {
    $source = file_get_contents($imagesView);

    expect($source)->toContain('<x-admin::media.image-viewer v-if="false" />')
        ->and($source)->toContain('<v-image-viewer')
        ->and($source)->toContain(':src="image.url"')
        ->and($source)->toContain(':file-name="getDisplayFileName(image)"');
});

it('routes preview to the full modal only when fullPreview is on', function () use ($imagesView) {
    $source = file_get_contents($imagesView);

    expect($source)->toContain('if (this.fullPreview) {')
        ->and($source)->toContain('this.$refs.imagePreviewModalFull.toggle();')
        ->and($source)->toContain('closeFullPreview()');
});

it('localizes the full-preview close control', function () use ($viewerView, $enLang) {
    $source = file_get_contents($viewerView);
    $lang = require $enLang;

    expect($source)->toContain("@lang('admin::app.components.media.image-viewer.close')")
        ->and($lang['components']['media'])->toHaveKey('image-viewer');
});

it('enables full preview in the default component invocation', function () {
    $html = Blade::render('<x-admin::media.image />');

    expect($html)->toContain('<v-media-image')
        ->and($html)->toContain('v-bind:full-preview="true"');
});

it('keeps the add tile last and matches the uploaded card height', function () use ($imagesView) {
    $source = file_get_contents($imagesView);

    expect(substr_count($source, ':style="{ ...tileStyle, order: 9999 }"'))->toBe(2)
        ->and($source)->toContain("responsive ? 'w-full min-h-[160px]' : ''")
        ->and($source)->toContain('height: `calc(${this.height} + 36px)`');
});
