<?php

use Illuminate\Support\Facades\Blade;

$cardView = __DIR__.'/../../../src/Resources/views/components/media/card.blade.php';
$imageView = __DIR__.'/../../../src/Resources/views/components/media/image.blade.php';
$imagesView = __DIR__.'/../../../src/Resources/views/components/media/image.blade.php';
$galleryView = __DIR__.'/../../../src/Resources/views/components/media/gallery.blade.php';
$fileView = __DIR__.'/../../../src/Resources/views/components/media/files.blade.php';

it('exposes a flexible shared media card API', function () use ($cardView) {
    $source = file_get_contents($cardView);

    expect($source)->toContain("app.component('v-media-card'")
        ->and($source)->toContain("emits: ['preview', 'replace', 'remove', 'select', 'drag-start', 'drag-end', 'drag-handle', 'update:selected']")
        ->and($source)->toContain('<slot name="actions" :media="media"></slot>')
        ->and($source)->not->toContain('acceptedTypes')
        ->and($source)->not->toContain('acceptedExtensions');
});

it('uses the shared card in image gallery and file item templates', function () use ($imagesView, $galleryView, $fileView) {
    expect(file_get_contents($imagesView))->toContain('<v-media-card')
        ->and(file_get_contents($galleryView))->toContain('<v-media-card')
        ->and(file_get_contents($fileView))->toContain('<v-media-card');
});

it('provides a single image public component without an allow multiple prop', function () use ($imageView) {
    $source = file_get_contents($imageView);

    expect($source)->not->toContain('allowMultiple')
        ->and($source)->not->toContain('allow-multiple');
});

it('renders the new image component entry point', function () {
    $html = Blade::render('<x-admin::media.image name="avatar" />');

    expect($html)->toContain('<v-media-image')
        ->and($html)->toContain('name="avatar"')
        ->and($html)->not->toContain('allow-multiple');
});

it('configures gallery validation by mime types and extensions', function () use ($galleryView) {
    $source = file_get_contents($galleryView);

    expect($source)->toContain("'acceptedTypes'")
        ->and($source)->toContain("'acceptedExtensions'")
        ->and($source)->toContain('isFileAccepted(file)')
        ->and($source)->toContain(':accept="acceptAttribute"');
});

it('provides independent MIME metadata for each gallery configuration', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin::media.gallery :accepted-extensions="['avif']" />
        <x-admin::media.gallery :accepted-extensions="['.JIF', 'psd']" />
        BLADE);

    preg_match_all("/:mime-types='([^']*)'/", $html, $matches);

    $maps = array_map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);

    expect($maps)->toHaveCount(2)
        ->and($maps[0])->toHaveKeys(['avif'])
        ->and($maps[0]['avif'])->toContain('image/avif')
        ->and($maps[0])->not->toHaveKey('psd')
        ->and($maps[1]['jif'])->toContain('image/jpeg')
        ->and($maps[1]['psd'])->toContain('application/photoshop')
        ->and($maps[1])->not->toHaveKey('avif');
});

it('allows a gallery caller to supply MIME metadata for a custom format', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin::media.gallery
            :accepted-extensions="['custom_image']"
            :mime-types="['custom_image' => ['image/x-custom']]"
        />
        BLADE);

    preg_match("/:mime-types='([^']*)'/", $html, $matches);

    expect($matches)->toHaveCount(2)
        ->and(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR))->toBe(['custom_image' => ['image/x-custom']]);
});

it('keeps wildcard-only galleries free from a default extension allowlist', function () {
    $html = Blade::render('<x-admin::media.gallery />');

    expect($html)->toContain(":mime-types='{}'")
        ->and($html)->toContain(":accepted-extensions='[]'");
});

it('renders AI suggestion labels as text instead of untrusted HTML', function () {
    $views = [
        __DIR__.'/../../../src/Resources/views/components/media/image.blade.php',
        __DIR__.'/../../../src/Resources/views/components/media/gallery.blade.php',
        __DIR__.'/../../../src/Resources/views/components/tinymce/index.blade.php',
        __DIR__.'/../../../src/Resources/views/configuration/magic-ai-prompt/index.blade.php',
    ];

    foreach ($views as $view) {
        $source = file_get_contents($view);

        expect($source)->toContain('element.textContent = item.original.name')
            ->and($source)->not->toContain('${item.original.name ||');
    }
});
