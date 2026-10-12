<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\get;

/*
 * The datagrid "select all matching" flow asks the grid endpoint for every primary id
 * in the current filter/search context (mass_action_ids=1) so a mass action can target
 * rows beyond the current page. These tests lock the backend contract for that request.
 */

it('returns every matching id across all pages for a select-all request', function () {
    $this->loginAsAdmin();

    $total = Attribute::count();

    // The default page size is 10, so a meaningful cross-page assertion needs more than one page.
    expect($total)->toBeGreaterThan(10);

    $response = get(
        route('admin.catalog.attributes.index', ['mass_action_ids' => 1]),
        ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    )->assertOk();

    $ids = $response->json('ids');

    expect($ids)->toBeArray()
        ->and($ids)->toHaveCount($total)
        ->and($ids)->toEqualCanonicalizing(Attribute::pluck('id')->all());

    // The lightweight id response must not carry the full paginated grid payload.
    expect($response->json('records'))->toBeNull();
});

it('returns the paginated grid payload when select-all is not requested', function () {
    $this->loginAsAdmin();

    $response = get(
        route('admin.catalog.attributes.index'),
        ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    )->assertOk();

    expect($response->json('ids'))->toBeNull()
        ->and($response->json('records'))->toBeArray()
        ->and($response->json('meta.total'))->toBe(Attribute::count());
});
