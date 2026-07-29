<?php

$viewOnly = ['dashboard', 'catalog', 'catalog.products'];

$permitted = ['dashboard', 'catalog', 'catalog.products', 'catalog.products.quick_export'];

$exportRequest = fn ($test, array $query) => $test
    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
    ->get(route('admin.catalog.products.quick-export', $query));

$gridRequest = fn ($test, array $query) => $test
    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
    ->get(route('admin.catalog.products.index', $query));

$exportQuery = fn (string $format): array => [
    'export'     => 1,
    'format'     => $format,
    'pagination' => ['page' => 1, 'per_page' => 10],
];

it('denies a quick export to a role holding only the product view permission', function () use ($viewOnly, $exportRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $viewOnly);

    $exportRequest($this, $exportQuery('csv'))->assertStatus(403);
});

it('allows a quick export to a role granted the quick export permission', function () use ($permitted, $exportRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $permitted);

    $exportRequest($this, $exportQuery('csv'))
        ->assertOk()
        ->assertDownload('products.csv');
});

it('denies a quick export for every offered format', function () use ($viewOnly, $exportRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $viewOnly);

    foreach (['csv', 'xls', 'xlsx'] as $format) {
        $exportRequest($this, $exportQuery($format))->assertStatus(403);
    }
});

it('allows a quick export for every offered format when permitted', function () use ($permitted, $exportRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $permitted);

    foreach (['csv', 'xls', 'xlsx'] as $format) {
        $exportRequest($this, $exportQuery($format))->assertDownload('products.'.$format);
    }
});

it('denies a quick export of explicitly selected rows', function () use ($viewOnly, $exportRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $viewOnly);

    $exportRequest($this, $exportQuery('csv') + ['productIds' => [1, 2]])->assertStatus(403);
});

it('sends a legacy listing route export on to the gated export route', function () use ($permitted, $gridRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $permitted);

    $gridRequest($this, $exportQuery('csv'))
        ->assertRedirect(route('admin.catalog.products.quick-export', $exportQuery('csv')));
});

it('never serves an export body from the grid listing route', function () use ($viewOnly, $gridRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $viewOnly);

    foreach (['csv', 'xls', 'xlsx'] as $format) {
        $gridRequest($this, $exportQuery($format))->assertRedirect();
    }
});

it('denies a view only role that follows the legacy listing route export', function () use ($viewOnly, $gridRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $viewOnly);

    $redirect = $gridRequest($this, $exportQuery('csv'))->assertRedirect()->headers->get('Location');

    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get($redirect)->assertForbidden();
});

it('still downloads for a permitted role that follows the legacy listing route export', function () use ($permitted, $gridRequest, $exportQuery) {
    $this->loginWithPermissions(permissions: $permitted);

    $redirect = $gridRequest($this, $exportQuery('csv'))->assertRedirect()->headers->get('Location');

    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get($redirect)->assertDownload('products.csv');
});

it('does not serve grid records from the export route when no export is asked for', function () use ($permitted) {
    $this->loginWithPermissions(permissions: $permitted);

    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.catalog.products.quick-export', ['pagination' => ['page' => 1, 'per_page' => 10]]))
        ->assertNotFound();
});

it('does not resolve matching ids from the export route', function () use ($permitted) {
    $this->loginWithPermissions(permissions: $permitted);

    $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.catalog.products.quick-export', ['mass_action_ids' => 1]))
        ->assertNotFound();
});

it('does not serve the export route to a plain browser navigation without an export flag', function () use ($permitted) {
    $this->loginWithPermissions(permissions: $permitted);

    $this->get(route('admin.catalog.products.quick-export'))->assertNotFound();
});

it('leaves the ordinary grid load reachable with only the view permission', function () use ($viewOnly, $gridRequest) {
    $this->loginWithPermissions(permissions: $viewOnly);

    $gridRequest($this, ['pagination' => ['page' => 1, 'per_page' => 10]])
        ->assertOk()
        ->assertJsonStructure(['records', 'meta']);

    $this->get(route('admin.catalog.products.index'))->assertOk();
});

it('hides the quick export button from a role without the permission', function () use ($viewOnly) {
    $this->loginWithPermissions(permissions: $viewOnly);

    $this->get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertDontSeeText(trans('admin::app.export.export'));
});

it('shows the quick export button to a role with the permission', function () use ($permitted) {
    $this->loginWithPermissions(permissions: $permitted);

    $this->get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.export.export'));
});

it('registers quick export as an assignable permission with a resolvable label', function () {
    expect(collect(config('acl'))->pluck('key'))
        ->toContain('catalog.products.quick_export');

    expect(trans('admin::app.acl.quick-export'))->toBe('Quick Export');
});
