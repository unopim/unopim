<?php

use Webkul\Category\Models\CategoryField;

it('should not display the category field list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.catalog.category_fields.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the category field list if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields']);

    $this->get(route('admin.catalog.category_fields.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.category_fields.index.title'));
});

it('should not display create form for creating the category field if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields']);

    $this->get(route('admin.catalog.category_fields.create'))
        ->assertSeeText('Unauthorized');
});

it('should display create form for category field if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields', 'catalog.category_fields.create']);

    $this->get(route('admin.catalog.category_fields.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.category_fields.create.title'));
});

it('should not display edit form for category field if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields']);
    $categoryField = CategoryField::factory()->create();

    $this->get(route('admin.catalog.category_fields.edit', ['id' => $categoryField->id]))
        ->assertSeeText('Unauthorized');
});

it('should display edit form for category field if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields', 'catalog.category_fields.edit']);
    $categoryField = CategoryField::factory()->create();

    $this->get(route('admin.catalog.category_fields.edit', $categoryField->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.category_fields.edit.title'));
});

it('should not be able to delete category field if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields']);
    $categoryField = CategoryField::factory()->create();

    $this->delete(route('admin.catalog.category_fields.delete', ['id' => $categoryField->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $categoryField->id]);
});

it('should be able to delete category field if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields', 'catalog.category_fields.delete']);

    $categoryField = CategoryField::factory()->create();

    $this->delete(route('admin.catalog.category_fields.delete', ['id' => $categoryField->id]))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), ['id' => $categoryField->id]);
});

it('should not be able to mass delete category fields if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields', 'catalog.category_fields.delete']);

    $categoryFieldIds = CategoryField::factory()->count(3)->create()->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_delete'), ['indices' => $categoryFieldIds])
        ->assertSeeText('Unauthorized');

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $id]);
    }
});

it('should be able to mass delete category fields if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields', 'catalog.category_fields.mass_delete']);

    $categoryFieldIds = CategoryField::factory()->count(3)->create()->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_delete'), ['indices' => $categoryFieldIds])
        ->assertOk();

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), ['id' => $id]);
    }
});

it('should not be able to mass update category fields if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields']);

    $categoryFieldIds = CategoryField::factory()->count(3)->create(['status' => 0])->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_update'), ['indices' => $categoryFieldIds, 'value' => 1])
        ->assertSeeText('Unauthorized');

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $id, 'status' => 0]);
    }
});

it('should be able to mass update category fields if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.category_fields', 'catalog.category_fields.mass_update']);

    $categoryFieldIds = CategoryField::factory()->count(3)->create(['status' => 0])->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_update'), ['indices' => $categoryFieldIds, 'value' => 1])
        ->assertOk();

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $id, 'status' => 1]);
    }
});
