<?php

$viewsPath = dirname(__DIR__, 2).'/src/Resources/views';

$layoutViews = [
    $viewsPath.'/components/layouts/index.blade.php',
    $viewsPath.'/components/layouts/anonymous.blade.php',
    $viewsPath.'/components/layouts/with-history/index.blade.php',
    $viewsPath.'/components/tinymce/index.blade.php',
    $viewsPath.'/emails/layout.blade.php',
];

it('ships admin views without third-party CDN assets', function () use ($layoutViews) {
    foreach ($layoutViews as $view) {
        $contents = file_get_contents($view);

        expect($contents)
            ->not->toContain('fonts.googleapis.com')
            ->not->toContain('fonts.gstatic.com')
            ->not->toContain('cdnjs.cloudflare.com')
            ->not->toContain('cdn.jsdelivr.net')
            ->not->toContain('unpkg.com');
    }
});

it('loads TinyMCE from the self-hosted build path', function () use ($viewsPath) {
    $contents = file_get_contents($viewsPath.'/components/tinymce/index.blade.php');

    expect($contents)
        ->toContain("asset('themes/admin/default/build/tinymce/tinymce.min.js')")
        ->toContain("base_url: '{{ asset('themes/admin/default/build/tinymce') }}'")
        ->toContain("suffix: '.min'");
});
