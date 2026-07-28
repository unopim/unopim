<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Webkul\Admin\DataGrids\Catalog\AssociationTypeDataGrid;

function seedUserDefinedAssociationType(string $code): int
{
    $id = DB::table('association_types')->insertGetId([
        'code'            => $code,
        'status'          => 1,
        'position'        => 99,
        'is_user_defined' => 1,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    DB::table('association_type_translations')->insert([
        'association_type_id'  => $id,
        'locale'               => 'en_US',
        'name'                 => 'Custom Association',
    ]);

    return $id;
}

it('returns the seeded default association type rows with the joined translation, status and position', function () {
    $rows = app(AssociationTypeDataGrid::class)->prepareQueryBuilder()->get()->keyBy('code');

    expect($rows)->toHaveKeys(['related_products', 'up_sells', 'cross_sells']);

    expect($rows['related_products']->name)->toBe('Related Products');
    expect($rows['related_products']->status)->toBe(1);
    expect($rows['related_products']->is_user_defined)->toBe(0);

    expect($rows['up_sells']->name)->toBe('Up Sells');
    expect($rows['cross_sells']->name)->toBe('Cross Sells');
});

it('does not expose the type column on the association type grid', function () {
    $sql = app(AssociationTypeDataGrid::class)->prepareQueryBuilder()->toSql();

    expect($sql)->not->toContain('`type`');
});

it('guards the delete action condition so default association types cannot be deleted while user-defined ones can', function () {
    $this->loginAsAdmin();

    $dataGrid = app(AssociationTypeDataGrid::class);

    $dataGrid->prepareActions();

    $actions = collect($dataGrid->getActions());

    $editAction = $actions->firstWhere('index', 'edit');
    $deleteAction = $actions->firstWhere('index', 'delete');

    expect($editAction)->not->toBeNull();
    expect($deleteAction)->not->toBeNull();
    expect($editAction->condition)->toBeNull();

    $defaultRow = (object) ['id' => 1, 'code' => 'related_products', 'is_user_defined' => 0];
    $userDefinedRow = (object) ['id' => 99, 'code' => 'custom_association', 'is_user_defined' => 1];

    expect($deleteAction->condition)->toBeCallable();
    expect(($deleteAction->condition)($defaultRow))->toBeFalse();
    expect(($deleteAction->condition)($userDefinedRow))->toBeTrue();
});

it('produces datagrid json where a default row has no delete action and a user-defined row does', function () {
    $this->loginAsAdmin();

    Route::get('_test/association-types/{id}/edit', fn () => null)->name('admin.catalog.association_types.edit');
    Route::delete('_test/association-types/{id}', fn () => null)->name('admin.catalog.association_types.delete');
    Route::post('_test/association-types/mass-delete', fn () => null)->name('admin.catalog.association_types.mass_delete');
    Route::post('_test/association-types/mass-update', fn () => null)->name('admin.catalog.association_types.mass_update');

    Route::getRoutes()->refreshNameLookups();

    seedUserDefinedAssociationType('custom_association');

    $response = app(AssociationTypeDataGrid::class)->toJson();

    $data = $response->getData(true);

    $records = collect($data['records'])->keyBy('code');

    expect($records)->toHaveKey('related_products');
    expect($records)->toHaveKey('custom_association');

    $defaultActionIndices = collect($records['related_products']['actions'])->pluck('index');
    $userDefinedActionIndices = collect($records['custom_association']['actions'])->pluck('index');

    expect($defaultActionIndices)->toContain('edit');
    expect($defaultActionIndices)->not->toContain('delete');

    expect($userDefinedActionIndices)->toContain('edit');
    expect($userDefinedActionIndices)->toContain('delete');
});
