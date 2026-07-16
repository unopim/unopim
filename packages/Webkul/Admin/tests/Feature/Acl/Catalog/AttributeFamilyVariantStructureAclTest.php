<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;

/**
 * Regression cover for the variant-structure routes: they were previously
 * absent from the ACL map entirely, which made the Bouncer middleware fail
 * OPEN (any authenticated admin could reach them regardless of permissions).
 */
it('should not list variant structures if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.variant-structures.index', ['id' => $attributeFamily->id]))
        ->assertStatus(403);
});

it('should list variant structures if have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.variant-structures']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.variant-structures.index', ['id' => $attributeFamily->id]))
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('should not display the variant structure edit form if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.variant-structures']);
    $attributeFamily = AttributeFamily::factory()->create();
    $structure = VariantStructure::create([
        'attribute_family_id' => $attributeFamily->id,
        'code'                => 'variant_structure_1',
        'levels'              => 1,
    ]);

    $this->get(route('admin.catalog.families.variant-structures.edit', [
        'id'          => $attributeFamily->id,
        'structureId' => $structure->id,
    ]))->assertStatus(403);
});

it('should display the variant structure edit form if have permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
        'catalog.families.variant-structures.edit',
    ]);
    $attributeFamily = AttributeFamily::factory()->create();
    $structure = VariantStructure::create([
        'attribute_family_id' => $attributeFamily->id,
        'code'                => 'variant_structure_1',
        'levels'              => 1,
    ]);

    $this->get(route('admin.catalog.families.variant-structures.edit', [
        'id'          => $attributeFamily->id,
        'structureId' => $structure->id,
    ]))->assertOk();
});

it('should not be able to save variant structures if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.variant-structures']);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->put(route('admin.catalog.families.variant-structures.save', ['id' => $attributeFamily->id]), [
        'structures' => [],
    ])->assertStatus(403);
});

it('should be able to save variant structures if have permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
        'catalog.families.variant-structures.edit',
    ]);
    $attributeFamily = AttributeFamily::factory()->create();

    $this->put(route('admin.catalog.families.variant-structures.save', ['id' => $attributeFamily->id]), [
        'structures' => [],
    ])->assertOk();
});

it('should not be able to delete a variant structure if does not have permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families', 'catalog.families.variant-structures']);
    $attributeFamily = AttributeFamily::factory()->create();
    $structure = VariantStructure::create([
        'attribute_family_id' => $attributeFamily->id,
        'code'                => 'variant_structure_1',
        'levels'              => 1,
    ]);

    $this->delete(route('admin.catalog.families.variant-structures.delete', [
        'id'          => $attributeFamily->id,
        'structureId' => $structure->id,
    ]))->assertStatus(403);

    $this->assertDatabaseHas($this->getFullTableName(VariantStructure::class), ['id' => $structure->id]);
});

it('should be able to delete a variant structure if has permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
        'catalog.families.variant-structures.delete',
    ]);
    $attributeFamily = AttributeFamily::factory()->create();
    $structure = VariantStructure::create([
        'attribute_family_id' => $attributeFamily->id,
        'code'                => 'variant_structure_1',
        'levels'              => 1,
    ]);

    $this->delete(route('admin.catalog.families.variant-structures.delete', [
        'id'          => $attributeFamily->id,
        'structureId' => $structure->id,
    ]))->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(VariantStructure::class), ['id' => $structure->id]);
});
