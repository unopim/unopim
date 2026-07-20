<?php

use Illuminate\Support\Facades\Blade;

$cardView = __DIR__.'/../../../src/Resources/views/components/media/card.blade.php';
$imageView = __DIR__.'/../../../src/Resources/views/components/media/image.blade.php';
$imagesView = __DIR__.'/../../../src/Resources/views/components/media/image.blade.php';
$galleryView = __DIR__.'/../../../src/Resources/views/components/media/gallery.blade.php';
$fileView = __DIR__.'/../../../src/Resources/views/components/media/file.blade.php';

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
