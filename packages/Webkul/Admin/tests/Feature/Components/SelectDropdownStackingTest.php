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
    'components/layouts/sidebar/index.blade.php',
]);

it('keeps the dropdown below the overlays that must cover it', function (string $view) {
    expect(dropdownStackingLevel())->toBeLessThan(highestLevelIn($view));
})->with([
    'components/drawer/index.blade.php',
    'components/modal/index.blade.php',
    'components/flash-group/index.blade.php',
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
    $manifest = json_decode(
        file_get_contents(base_path('public/themes/admin/default/build/manifest.json')),
        true
    );

    $entry = collect($manifest)->firstWhere(fn (array $item): bool => str_ends_with($item['file'] ?? '', '.css')
        && str_starts_with($item['file'], 'assets/app-'));

    $built = file_get_contents(base_path('public/themes/admin/default/build/'.$entry['file']));

    expect($built)->toContain('.multiselect--active')
        ->toContain('z-index:'.dropdownStackingLevel());
});
