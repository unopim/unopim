<?php

use function Pest\Laravel\get;

it('renders the datagrid toolbar so it condenses on its own width instead of widening the page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    $toolbar = [];

    preg_match('/class="datagrid-toolbar[^"]*"/', $response->getContent(), $toolbar);

    expect($toolbar)->not->toBeEmpty()
        ->and($toolbar[0])->toContain('group/toolbar')
        ->and($toolbar[0])->toContain('[&.is-stacked]:flex-wrap')
        ->and($toolbar[0])->not->toContain('max-md:flex-wrap')
        ->and($response->getContent())->toContain(':class="toolbarLayout"');
});

it('lets the search shrink so the toolbar controls stay on one line', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())->toContain('class="flex min-w-0 shrink gap-x-1"')
        ->and($response->getContent())->toContain('class="flex w-full min-w-0 max-w-xs shrink items-center max-sm:w-full max-sm:max-w-full"');
});

it('hides the toolbar trigger labels once the toolbar is condensed', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect(substr_count($response->getContent(), 'group-[.is-condensed]/toolbar:sr-only'))->toBeGreaterThanOrEqual(3);
});

it('docks the toolbar controls into a bottom bar on phone widths', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())->toContain('group-[.is-stacked]/toolbar:fixed')
        ->and($response->getContent())->toContain('group-[.is-stacked]/toolbar:bottom-0')
        ->and($response->getContent())->toContain('group-[.is-stacked]/toolbar:justify-around');
});

it('renders the filter drawer above the admin header', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())->toContain('fixed z-[10002] bg-white dark:bg-cherry-800 max-sm:!w-full');
});

it('stacks the docked toolbar bar above the admin header so its overlays escape', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())
        ->toContain('group-[.is-stacked]/toolbar:bottom-0 group-[.is-stacked]/toolbar:z-[10003]');
});

it('renders the main content area guarded against horizontal page scroll', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    $main = [];

    preg_match('/<main id="main-content"[^>]*class="[^"]*"/', $response->getContent(), $main);

    expect($main)->not->toBeEmpty()
        ->and($main[0])->toContain('overflow-x-hidden');
});

it('collapses the bottom bar triggers to icons only on phone widths', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())->toContain("'is-stacked':   true,")
        ->and($response->getContent())->toContain("'is-condensed': true,");
});

it('drops a stale mass selection when the applied filters change', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())->toContain("'applied.filters': {")
        ->and($response->getContent())->toContain('this.clearMassSelection();');
});

it('keeps the manage columns panes inside the viewport on small screens', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    expect($response->getContent())->toContain('grid grid-cols-2 max-sm:grid-cols-1 gap-4 p-2')
        ->and($response->getContent())->toContain('h-[min(55vh,calc(100vh-367px))]')
        ->and($response->getContent())->not->toContain('h-[calc(100vh-285px)]');
});
