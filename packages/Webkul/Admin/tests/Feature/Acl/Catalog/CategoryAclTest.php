<?php

use Webkul\Category\Models\Category;

it('should not display the category list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.catalog.categories.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the category list if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories']);

    $this->get(route('admin.catalog.categories.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.index.title'));
});

it('should not display create form for creating the category if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories']);

    $this->get(route('admin.catalog.categories.create'))
        ->assertSeeText('Unauthorized');
});

it('should display create form for category if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories', 'catalog.categories.create']);

    $this->get(route('admin.catalog.categories.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.create.title'));
});

it('should not display edit form for category if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories']);
    $category = Category::factory()->create();

    $this->get(route('admin.catalog.categories.edit', ['id' => $category->id]))
        ->assertSeeText('Unauthorized');
});

it('should display edit form for category if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories', 'catalog.categories.edit']);
    $category = Category::factory()->create();

    $this->get(route('admin.catalog.categories.edit', $category->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.edit.title'));
});

it('should not be able to delete category if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories']);
    $category = Category::factory()->create();

    $this->delete(route('admin.catalog.categories.delete', ['id' => $category->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('should be able to delete category if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.categories', 'catalog.categories.delete']);

    $category = Category::factory()->create();

    $this->delete(route('admin.catalog.categories.delete', ['id' => $category->id]))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), ['id' => $category->id]);
});
