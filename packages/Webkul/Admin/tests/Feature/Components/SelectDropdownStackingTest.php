<?php

function adminStylesheet(): string
{
    return file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/assets/css/app.css')
    );
}

function multiselectStylesheetPath(): string
{
    return base_path('node_modules/vue-multiselect/dist/vue-multiselect.css');
}

function dropdownStackingLevel(): int
{
    preg_match(
        '/\.multiselect--active[^{]*\{[^}]*z-index:\s*(\d+)/',
        adminStylesheet(),
        $matches
    );

    return (int) ($matches[1] ?? 0);
}

function builtStylesheet(): string
{
    $manifest = json_decode(
        file_get_contents(base_path('public/themes/admin/default/build/manifest.json')),
        true
    );

    $entry = collect($manifest)->firstWhere(fn (array $item): bool => str_ends_with($item['file'] ?? '', '.css')
        && str_starts_with($item['file'], 'assets/app-'));

    return file_get_contents(base_path('public/themes/admin/default/build/'.$entry['file']));
}

function levelsIn(string $view): array
{
    preg_match_all(
        '/z-\[(\d+)\]/',
        file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/'.$view)),
        $matches
    );

    return array_values(array_unique(array_map('intval', $matches[1])));
}

function highestLevelIn(string $view): int
{
    $markup = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/'.$view));

    preg_match_all('/z-\[(\d+)\]/', $markup, $matches);

    return $matches[1] === [] ? 0 : max(array_map('intval', $matches[1]));
}

it('raises the open dropdown above the layers it used to hide behind', function (string $view) {
    expect(dropdownStackingLevel())->toBeGreaterThan(highestLevelIn($view));
})->with([
    'components/form/unsaved-changes.blade.php',
]);

it('keeps the dropdown below the overlays that must cover it', function (string $view) {
    expect(dropdownStackingLevel())->toBeLessThan(highestLevelIn($view));
})->with([
    'components/layouts/edit-page-header.blade.php',
    'components/layouts/with-history/index.blade.php',
    'components/layouts/sidebar/index.blade.php',
    'components/drawer/index.blade.php',
    'components/modal/index.blade.php',
    'components/flash-group/index.blade.php',
]);

/**
 * The header carries the tab strip and stays pinned while the page scrolls, so
 * a dropdown opening upwards over it hides where the user is in the record.
 */
it('slides the open dropdown under the header the tabs are pinned to', function (string $view) {
    expect(highestLevelIn($view))->toBeGreaterThan(dropdownStackingLevel())
        ->and(highestLevelIn($view))->toBeLessThan(highestLevelIn('components/layouts/sidebar/index.blade.php'));
})->with([
    'components/layouts/edit-page-header.blade.php',
    'components/layouts/with-history/index.blade.php',
]);

/**
 * The collapsed sidebar throws its submenu across the content column, over the
 * band the header occupies, so it has to outrank the header. It sits level with
 * the overlays instead of over them: those are declared further down the page
 * and win the tie, which is how the top bar has always ridden along.
 */
it('leaves the sidebar on top, its flyout reaches across that header', function () {
    $sidebar = highestLevelIn('components/layouts/sidebar/index.blade.php');

    expect($sidebar)->toBe(highestLevelIn('components/layouts/header/index.blade.php'))
        ->and($sidebar)->toBeLessThanOrEqual(highestLevelIn('components/modal/index.blade.php'));
});

/**
 * The admin loads a prebuilt stylesheet, and an arbitrary value Tailwind has
 * never seen is compiled into nothing at all: the class lands on the element
 * and the layer silently stays where it was.
 */
it('stacks with levels the built stylesheet already carries', function (string $view) {
    $built = builtStylesheet();

    foreach (levelsIn($view) as $level) {
        expect($built)->toContain('.z-\['.$level.'\]{z-index:'.$level.'}');
    }
})->with([
    'components/layouts/edit-page-header.blade.php',
    'components/layouts/with-history/index.blade.php',
    'components/layouts/sidebar/index.blade.php',
]);

it('leaves the library default behind, which was below every one of those', function () {
    $library = file_get_contents(multiselectStylesheetPath());

    preg_match('/\.multiselect--active\s*\{\s*z-index:\s*(\d+)/', $library, $matches);

    expect((int) ($matches[1] ?? 0))->toBe(50)
        ->and(dropdownStackingLevel())->toBeGreaterThan(50);
})->skip(
    fn (): bool => ! file_exists(multiselectStylesheetPath()),
    'vue-multiselect is not installed',
);

it('ships the rule in the built stylesheet the admin actually loads', function () {
    $built = builtStylesheet();

    expect($built)->toContain('.multiselect--active')
        ->toContain('z-index:'.dropdownStackingLevel())
        ->toContain('z-index:'.highestLevelIn('components/layouts/with-history/index.blade.php'))
        ->toContain('z-index:'.highestLevelIn('components/layouts/sidebar/index.blade.php'));
});
