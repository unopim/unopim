<?php

use Illuminate\Support\Facades\Blade;

it('labels the search input with its placeholder', function () {
    expect(Blade::render('<x-admin::search placeholder="Find products" />'))
        ->toContain('aria-label="Find products"');
});

it('binds the accessible name to a dynamic placeholder', function () {
    expect(Blade::render('<x-admin::search ::placeholder="available.searchPlaceholder" />'))
        ->toContain(':aria-label="available.searchPlaceholder"');
});

it('falls back to a translated search label', function () {
    expect(Blade::render('<x-admin::search />'))
        ->toContain('aria-label="'.trans('admin::app.components.search.placeholder').'"');
});

it('keeps an explicit accessible name', function () {
    $html = Blade::render('<x-admin::search aria-label="Filter rows" placeholder="Find" />');

    expect($html)->toContain('aria-label="Filter rows"')
        ->and(substr_count($html, 'aria-label='))->toBe(1);
});
