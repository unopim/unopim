<?php

const EDITOR_LIGHT_SURFACE = '237 233 254';

const EDITOR_TOOLBAR_BUTTON = '.tox .tox-toolbar__group:last-child button';

const EDITOR_TOOLBAR_HOVER = '.tox .tox-tbtn:hover';

function compiledAdminStyles(): string
{
    $build = base_path('public/themes/admin/default/build');

    $manifest = json_decode(file_get_contents($build.'/manifest.json'), true);

    return file_get_contents($build.'/'.$manifest['src/Resources/assets/css/app.css']['file']);
}

function compiledRule(string $selector): string
{
    $styles = compiledAdminStyles();

    $offset = strpos($styles, $selector.'{');

    expect($offset)->not->toBeFalse("no compiled rule for {$selector}");

    $offset += strlen($selector) + 1;

    return substr($styles, $offset, strpos($styles, '}', $offset) - $offset);
}

function darkVariantOf(string $selector): string
{
    return $selector.':is(.dark *)';
}

it('does not leave the toolbar buttons on a light surface in dark mode', function () {
    expect(compiledRule(darkVariantOf(EDITOR_TOOLBAR_BUTTON)))
        ->not->toContain(EDITOR_LIGHT_SURFACE);
});

it('does not leave a hovered toolbar button on a light surface in dark mode', function () {
    expect(compiledRule(darkVariantOf(EDITOR_TOOLBAR_HOVER)))
        ->not->toContain(EDITOR_LIGHT_SURFACE);
});

it('draws the toolbar icons white in dark mode', function () {
    expect(compiledRule('.dark .tox .tox-tbtn svg'))->toContain('fill:#fff');
});

it('lets the dark rules override the light ones', function (string $selector) {
    $styles = compiledAdminStyles();

    expect(strpos($styles, darkVariantOf($selector).'{'))
        ->toBeGreaterThan(strpos($styles, $selector.'{'));
})->with([EDITOR_TOOLBAR_BUTTON, EDITOR_TOOLBAR_HOVER]);

it('keeps the light surface for the same buttons outside dark mode', function (string $selector) {
    expect(compiledRule($selector))->toContain(EDITOR_LIGHT_SURFACE);
})->with([EDITOR_TOOLBAR_BUTTON, EDITOR_TOOLBAR_HOVER]);

it('serves the compiled stylesheet the manifest points at', function () {
    $build = base_path('public/themes/admin/default/build');

    $manifest = json_decode(file_get_contents($build.'/manifest.json'), true);

    expect($build.'/'.$manifest['src/Resources/assets/css/app.css']['file'])->toBeReadableFile();
});

it('carries every dark toolbar rule from the source into the build', function () {
    $source = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/assets/css/app.css'));

    preg_match_all('/\.tox[^{]*\{[^}]*dark:[^}]*}/', $source, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $rule) {
        preg_match('/^(\.tox[^{]*)\{/', $rule, $selector);

        expect(compiledAdminStyles())->toContain(darkVariantOf(trim($selector[1])).'{');
    }
});
