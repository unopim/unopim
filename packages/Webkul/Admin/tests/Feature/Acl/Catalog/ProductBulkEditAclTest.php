<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;

$viewOnly = ['dashboard', 'catalog', 'catalog.products'];

$editOnly = ['dashboard', 'catalog', 'catalog.products', 'catalog.products.edit'];

$bulkEdit = ['dashboard', 'catalog', 'catalog.products', 'catalog.products.bulk_edit'];

$massActionTitles = function (): array {
    $grid = app(ProductDataGrid::class);

    $grid->prepareMassActions();

    return collect($grid->getMassActions())->pluck('title')->all();
};

it('denies every bulk edit entry point to a role holding only the product view permission', function () use ($viewOnly) {
    $this->loginWithPermissions('custom', $viewOnly);

    $this->post(route('admin.catalog.products.bulkedit.filters'))->assertStatus(403);

    $this->get(route('admin.catalog.products.bulkedit'))->assertStatus(403);

    $this->get(route('admin.catalog.bulkedit.attributes.fetch-all'))->assertStatus(403);

    $this->post(route('admin.catalog.products.bulk-edit.save'))->assertStatus(403);
});

it('denies bulk edit to a role that can edit a single product but was not granted bulk edit', function () use ($editOnly) {
    $this->loginWithPermissions('custom', $editOnly);

    $this->post(route('admin.catalog.products.bulk-edit.save'))->assertStatus(403);

    $this->get(route('admin.catalog.products.bulkedit'))->assertStatus(403);
});

it('allows the bulk edit entry points once the permission is granted', function () use ($bulkEdit) {
    $this->loginWithPermissions('custom', $bulkEdit);

    expect($this->post(route('admin.catalog.products.bulkedit.filters'))->status())->not->toBe(403);

    expect($this->get(route('admin.catalog.products.bulkedit'))->status())->not->toBe(403);

    $this->get(route('admin.catalog.bulkedit.attributes.fetch-all'))->assertSuccessful();
});

it('shows the bulk edit mass action only to a role granted bulk edit', function () use ($bulkEdit, $massActionTitles) {
    $this->loginWithPermissions('custom', $bulkEdit);

    expect($massActionTitles())->toContain(trans('admin::app.catalog.products.bulk-edit.action'));
});

it('hides the bulk edit mass action from a role granted only mass update', function () use ($massActionTitles) {
    $this->loginWithPermissions('custom', [
        'dashboard',
        'catalog',
        'catalog.products',
        'catalog.products.mass_update',
    ]);

    expect($massActionTitles())
        ->not->toContain(trans('admin::app.catalog.products.bulk-edit.action'))
        ->toContain(trans('admin::app.catalog.products.index.datagrid.update-status'));
});

it('shows the mass delete action to a role granted mass delete without mass update', function () use ($massActionTitles) {
    $this->loginWithPermissions('custom', [
        'dashboard',
        'catalog',
        'catalog.products',
        'catalog.products.mass_delete',
    ]);

    expect($massActionTitles())
        ->toContain(trans('admin::app.catalog.products.index.datagrid.delete'))
        ->not->toContain(trans('admin::app.catalog.products.index.datagrid.update-status'));
});

it('registers bulk edit as an assignable permission with a resolvable label', function () {
    expect(collect(config('acl'))->pluck('key'))->toContain('catalog.products.bulk_edit');

    expect(trans('admin::app.acl.bulk-edit'))->toBe('Bulk Edit');
});
