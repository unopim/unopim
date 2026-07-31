<?php

use Webkul\Core\Models\Locale;

use function Pest\Laravel\get;

/*
 * Functional coverage for the datagrid "select all matching" flow on a real, multi-page
 * grid (Settings > Locales, 200+ rows / 20+ pages). Verifies the backend contract the
 * header-checkbox dropdown relies on: one request resolves every id across all pages,
 * and the paginated payload advertises the metadata the UI gates the dropdown on.
 */

it('resolves every locale id across all pages for a select-all request', function () {
    $this->loginAsAdmin();

    $total = Locale::count();

    expect($total)->toBeGreaterThan(10);

    $response = get(
        route('admin.settings.locales.index', ['mass_action_ids' => 1]),
        ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    )->assertOk();

    $ids = $response->json('ids');

    expect($ids)->toBeArray()
        ->and($ids)->toHaveCount($total)
        ->and($ids)->toEqualCanonicalizing(Locale::pluck('id')->all());

    expect($response->json('records'))->toBeNull();
});

it('exposes multi-page metadata that enables the select-all dropdown', function () {
    $this->loginAsAdmin();

    $response = get(
        route('admin.settings.locales.index'),
        ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    )->assertOk();

    expect($response->json('ids'))->toBeNull()
        ->and($response->json('records'))->toBeArray()
        ->and($response->json('meta.total'))->toBe(Locale::count())
        // The dropdown only renders when the grid spans more than one page and allows select-all.
        ->and($response->json('meta.last_page'))->toBeGreaterThan(1)
        ->and($response->json('meta.select_all_enabled'))->toBeTrue();
});
