<?php

use Webkul\Product\Models\Product;

use function Pest\Laravel\get;

/*
 * Blade-render smoke tests for admin pages that render the datagrid toolbar
 * and heavy edit views. These guard against Blade-compile / render regressions
 * of the kind introduced by the per-page-option (`::key="perPageOption"`) and
 * related datagrid-toolbar changes: a broken toolbar/edit view surfaces here as
 * a 500 instead of a 200.
 */

it('renders the users index page (datagrid toolbar)', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.users.index'))
        ->assertOk()
        ->assertViewIs('admin::settings.users.index');
});

it('renders the attribute families index page (datagrid toolbar)', function () {
    $this->loginAsAdmin();

    get(route('admin.catalog.families.index'))
        ->assertOk()
        ->assertViewIs('admin::catalog.families.index');
});

it('renders the product edit page', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();

    get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertViewIs('admin::catalog.products.edit');
});
