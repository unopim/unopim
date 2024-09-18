<?php

use Webkul\Attribute\Models\Attribute;

it('should not display the attribute list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.catalog.attributes.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the attribute list if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes']);

    $this->get(route('admin.catalog.attributes.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.attributes.index.title'));
});

it('should not display create form for creating the attribute if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes']);

    $this->get(route('admin.catalog.attributes.create'))
        ->assertSeeText('Unauthorized');
});

it('should display create form for attribute if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes', 'catalog.attributes.create']);

    $this->get(route('admin.catalog.attributes.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.attributes.create.title'));
});

it('should not display edit form for attribute if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes']);
    $attribute = Attribute::factory()->create();

    $this->get(route('admin.catalog.attributes.edit', ['id' => $attribute->id]))
        ->assertSeeText('Unauthorized');
});

it('should display edit form for attribute if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes', 'catalog.attributes.edit']);
    $attribute = Attribute::factory()->create();

    $this->get(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.attributes.edit.title'));
});

it('should not be able to delete attribute if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes']);
    $attribute = Attribute::factory()->create();

    $this->delete(route('admin.catalog.attributes.delete', ['id' => $attribute->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $attribute->id]);
});

it('should be able to delete attribute if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attributes', 'catalog.attributes.delete']);

    $attribute = Attribute::factory()->create();

    $this->delete(route('admin.catalog.attributes.delete', ['id' => $attribute->id]))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $attribute->id]);
});
