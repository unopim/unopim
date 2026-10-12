<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\get;

/**
 * Raising the header over an open dropdown hands it the clicks in that band as
 * well as the paint, so a panel reaching under it went unreachable rather than
 * merely hidden. The guard keeps the panel out of the band to begin with.
 *
 * @see SelectDropdownStackingTest — the stacking order this compensates for
 */
function guardSource(): string
{
    return file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/components/layouts/sticky-header-dropdown-guard.blade.php')
    );
}

function stickyHeaderView(string $view): string
{
    return file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/components/layouts/'.$view));
}

it('reaches every view that pins a header, once between them', function (string $view) {
    expect(stickyHeaderView($view))->toContain('<x-admin::layouts.sticky-header-dropdown-guard />');
})->with([
    'edit-page-header.blade.php',
    'with-history/index.blade.php',
]);

it('pushes under one key so the pair does not ship it twice', function () {
    expect(guardSource())->toContain("@pushOnce('scripts', 'sticky-header-dropdown-guard')");
});

it('measures the header it has to clear rather than assuming a height', function () {
    expect(guardSource())->toContain(".querySelector('.js-sticky-header')")
        ->and(guardSource())->toContain('getBoundingClientRect().bottom + GAP');
});

it('gives the panel room by scrolling before it caps it', function () {
    $source = guardSource();

    expect($source)->toContain("closest('#main-content')")
        ->and($source)->toContain('scroller.scrollTop -= Math.min(overlap, scroller.scrollTop)')
        ->and($source)->toContain('panel.style.maxHeight = Math.max(MIN_PANEL_HEIGHT, rect.bottom - limit)');
});

it('drops the cap again when the panel closes', function () {
    $source = guardSource();

    expect($source)->toContain('const release = (multiselect)')
        ->and($source)->toContain("target.classList.contains('multiselect--active')");
});

/**
 * The admin loads a prebuilt bundle, so anything routed through app.js would
 * need a Vite run to reach a browser. This ships in the markup instead.
 */
it('rides in the markup rather than the built bundle', function () {
    expect(guardSource())->toContain('<script>')
        ->and(guardSource())->not->toContain('import ');
});

it('lands on a page that pins a header, exactly once', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'text']);

    $page = get(route('admin.catalog.attributes.edit', ['id' => $attribute->id]))
        ->assertOk()
        ->getContent();

    expect(substr_count($page, "querySelector('.js-sticky-header')"))->toBe(1)
        ->and($page)->toContain('MutationObserver');
});
