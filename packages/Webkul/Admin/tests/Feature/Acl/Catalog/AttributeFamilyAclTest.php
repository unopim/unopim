<?php

use Webkul\Attribute\Models\AttributeFamily;

it('should not display the attribute family list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.catalog.families.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the attribute family list if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);

    $this->get(route('admin.catalog.families.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.index.title'));
});

it('should not display create form for creating the attribute family if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);

    $this->get(route('admin.catalog.families.create'))
        ->assertSeeText('Unauthorized');
});

it('should display create form of attribute family if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.create']);

    $this->get(route('admin.catalog.families.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.index.add'));
});

it('should not display the attiribute family if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);
    $attributeFamily = AttributeFamily::first();

    $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]))
        ->assertSeeText('Unauthorized');
});

it('should display the attibute family edit if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.edit']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.edit', $attributeFamily->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.edit.title'));
});

it('should not be able to create copy of the attibute family if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.copy', $attributeFamily->id))
        ->assertSeeText('Unauthorized');
});

it('should be able to create copy of the attibute family if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.copy']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.copy', $attributeFamily->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.index.add'));
});

it('should not be able to delete attribute family if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->delete(route('admin.catalog.families.delete', ['id' => $attributeFamily->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), ['id' => $attributeFamily->id]);
});

it('should be able to delete attribute family if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.delete']);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->delete(route('admin.catalog.families.delete', ['id' => $attributeFamily->id]))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeFamily::class), ['id' => $attributeFamily->id]);
});
