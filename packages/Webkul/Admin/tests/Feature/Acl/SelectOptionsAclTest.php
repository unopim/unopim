<?php

dataset('select option entities', [
    'attributes',
    'category_fields',
    'channel',
    'locale',
    'currency',
]);

it('denies select options to an admin without a matching permission', function (string $entityName): void {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->getJson(route('admin.vue_js_select.select.options', ['entityName' => $entityName]))
        ->assertForbidden();
})->with('select option entities');

it('allows select options to an admin with the entity permission', function (string $entityName, array $permissions): void {
    $this->loginWithPermissions(permissions: ['dashboard', ...$permissions]);

    $this->getJson(route('admin.vue_js_select.select.options', ['entityName' => $entityName]))
        ->assertOk()
        ->assertJsonStructure(['options', 'page', 'lastPage']);
})->with([
    'attributes'      => ['attributes', ['catalog', 'catalog.attributes']],
    'category_fields' => ['category_fields', ['catalog', 'catalog.category_fields']],
    'channel'         => ['channel', ['settings', 'settings.channels']],
    'locale'          => ['locale', ['settings', 'settings.locales']],
    'currency'        => ['currency', ['settings', 'settings.currencies']],
]);

it('allows attribute options to an admin who can edit products', function (): void {
    $this->loginWithPermissions(permissions: ['dashboard', 'catalog', 'catalog.products']);

    $this->getJson(route('admin.vue_js_select.select.options', ['entityName' => 'attributes']))
        ->assertOk();
});

it('allows every entity to an admin with all permissions', function (string $entityName): void {
    $this->loginAsAdmin();

    $this->getJson(route('admin.vue_js_select.select.options', ['entityName' => $entityName]))
        ->assertOk();
})->with('select option entities');

it('rejects a filter column that is not a plain column name', function (): void {
    $this->loginAsAdmin();

    $this->getJson(route('admin.vue_js_select.select.options', [
        'entityName' => 'attributes',
        'filters'    => [['column' => 'code) or (1=1', 'operator' => '=', 'value' => 'sku']],
    ]))->assertUnprocessable()->assertJsonValidationErrors('filters.0.column');
});

it('still filters on a plain column name', function (): void {
    $this->loginAsAdmin();

    $this->getJson(route('admin.vue_js_select.select.options', [
        'entityName' => 'attributes',
        'filters'    => [['column' => 'code', 'operator' => '=', 'value' => 'sku']],
    ]))->assertOk()->assertJsonPath('options.0.code', 'sku');
});

it('keeps the datagrid column picker open to an admin without catalog permissions', function (): void {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->getJson(route('admin.datagrid.available_columns', ['entityName' => 'attributes', 'source' => 'product']))
        ->assertOk();
});
