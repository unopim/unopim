<?php

use Webkul\Attribute\Models\AttributeGroup;

it('should not display the attribute group list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.catalog.attribute.groups.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the attribute group list if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups']);

    $this->get(route('admin.catalog.attribute.groups.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.attribute-groups.index.title'));
});

it('should not display create form for creating the attribute group if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups']);

    $this->get(route('admin.catalog.attribute.groups.create'))
        ->assertSeeText('Unauthorized');
});

it('should display create form for attribute group if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups', 'catalog.attribute_groups.create']);

    $this->get(route('admin.catalog.attribute.groups.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.attribute-groups.create.title'));
});

it('should not display edit form for attribute group if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups']);
    $attributeGroup = AttributeGroup::factory()->create();

    $this->get(route('admin.catalog.attribute.groups.edit', ['id' => $attributeGroup->id]))
        ->assertSeeText('Unauthorized');
});

it('should display edit form for attribute group if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups', 'catalog.attribute_groups.edit']);
    $attributeGroup = AttributeGroup::factory()->create();

    $this->get(route('admin.catalog.attribute.groups.edit', $attributeGroup->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.attribute-groups.edit.title'));
});

it('should not be able to delete attribute group if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups']);
    $attributeGroup = AttributeGroup::factory()->create();

    $this->delete(route('admin.catalog.attribute.groups.delete', ['id' => $attributeGroup->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(AttributeGroup::class), ['id' => $attributeGroup->id]);
});

it('should be able to delete attribute group if has permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.attribute_groups', 'catalog.attribute_groups.delete']);
    $attributeGroup = AttributeGroup::factory()->create();

    $this->delete(route('admin.catalog.attribute.groups.delete', ['id' => $attributeGroup->id]))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeGroup::class), ['id' => $attributeGroup->id]);
});
